<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : Singular PascalCase module name, e.g. Product}
        {--schema= : Path to the YAML schema file (default: module-schemas/{name}.yaml)}
        {--dry-run : List the files that would be created/modified without writing anything}
        {--force : Overwrite files that already exist}';

    protected $description = 'Generate a full CRUD module (migration, model, repository, service, requests, resource, controller, routes) from a YAML schema, deterministically and without AI involvement.';

    protected $help = <<<'HELP'
        Generates a complete CRUD module end-to-end from a YAML schema file, following
        this repo's Laravel conventions (repository pattern, guid-based routing, Spatie
        permissions, Criteria-based filtering).

        Usage:
          php artisan make:module Product
          php artisan make:module Product --schema=module-schemas/custom.yaml
          php artisan make:module Product --dry-run
          php artisan make:module Product --force

        Schema file (default location: module-schemas/{Name}.yaml):

          name: Product
          softDeletes: false
          seeder: false
          fields:
            - name: title
              type: string
              nullable: false
            - name: price
              type: "decimal:8,2"
              nullable: false
            - name: description
              type: text
              nullable: true
          relations:
            - name: category
              type: belongsTo
              model: Category
              nullable: false
          permissions:
            module: products

        Supported field types: string, text, integer, "decimal:P,S", boolean, date, datetime.
        Supported relation types: belongsTo (adds a migration FK column, model relation,
        request field, and service resolution), hasMany, hasOne, belongsToMany (model
        relation method only — pivot tables/migrations for belongsToMany are not
        generated and must be created by hand).

        What gets generated:
          - database/migrations/{timestamp}_create_{table}_table.php
          - app/Models/{Name}.php
          - app/Contracts/Repositories/{Name}RepositoryInterface.php
          - app/Repositories/{Name}RepositoryEloquent.php
          - app/Services/{Name}Service.php
          - app/Http/Requests/{Names}/Create{Name}Request.php
          - app/Http/Requests/{Names}/Update{Name}Request.php
          - app/Http/Resources/V1/{Name}Resource.php
          - app/Http/Controllers/V1/{Name}Controller.php
          - routes/api/{kebab-plural}.php (auto-included by routes/api.php's glob)

        Also modifies:
          - app/Providers/AppServiceProvider.php — inserts the repository binding at the
            "module-generator:repository-bindings" anchor comment inside register().

        Not modified automatically (printed for manual review instead):
          - database/seeders/PermissionSeeder.php and RoleSeeder.php — the command prints
            the exact snippet to paste; assigning permissions to roles is a business
            decision, not a mechanical one.

        After a real (non-dry-run) run, `php artisan migrate --pretend` is executed
        automatically so you can catch schema errors immediately.
        HELP;

    private const FIELD_TYPES = ['string', 'text', 'integer', 'boolean', 'date', 'datetime'];

    private const RELATION_TYPES = ['belongsTo', 'hasMany', 'hasOne', 'belongsToMany'];

    /**
     * Applied to the raw module name and to every field/relation name and relation
     * model before any of them are interpolated into generated PHP or file paths —
     * blocks both path traversal (no '/', '\', '.') and PHP code/string injection
     * (no quotes, semicolons, etc.) from a malformed or malicious schema file.
     */
    private const IDENTIFIER_PATTERN = '/^[A-Za-z][A-Za-z0-9_-]*$/';

    private const DECIMAL_TYPE_PATTERN = '/^decimal:(\d+),(\d+)$/';

    public function handle(): int
    {
        $rawName = $this->argument('name');
        if (! preg_match(self::IDENTIFIER_PATTERN, $rawName)) {
            $this->error("Invalid module name '{$rawName}'. Use letters, digits, '_' or '-' only, starting with a letter.");

            return self::FAILURE;
        }
        $names = $this->deriveNames($rawName);

        $schemaPath = $this->resolveSchemaPath($names['studly']);
        if (! File::exists($schemaPath)) {
            $this->error("Schema file not found: {$schemaPath}");
            $this->line('Pass --schema=path/to/file.yaml or create module-schemas/'.$names['studly'].'.yaml');

            return self::FAILURE;
        }

        $schema = Yaml::parseFile($schemaPath);
        if (! is_array($schema)) {
            $this->error("Schema file is empty or invalid: {$schemaPath}");

            return self::FAILURE;
        }

        $rawFields = $schema['fields'] ?? [];
        $rawRelations = $schema['relations'] ?? [];

        $validationError = $this->validateSchemaShape($rawFields, $rawRelations);
        if ($validationError !== null) {
            $this->error($validationError);

            return self::FAILURE;
        }

        $fields = $this->normalizeFields($rawFields);
        $relations = $this->normalizeRelations($rawRelations);
        $softDeletes = (bool) ($schema['softDeletes'] ?? false);
        $wantsSeeder = (bool) ($schema['seeder'] ?? false);
        $moduleName = $schema['permissions']['module'] ?? $names['kebabPlural'];

        $targets = $this->resolveTargets($names, $wantsSeeder);

        $conflicts = array_filter($targets, fn (string $path) => File::exists($path));
        if (! empty($conflicts) && ! $this->option('force')) {
            $this->error('The following files already exist. Re-run with --force to overwrite them:');
            foreach ($conflicts as $key => $path) {
                $this->line("  - {$path}");
            }

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — the following files would be created/modified:');
            foreach ($targets as $key => $path) {
                $this->line("  - {$path}");
            }
            $this->line('  - '.app_path('Providers/AppServiceProvider.php').' (repository binding inserted)');

            return self::SUCCESS;
        }

        $providerPath = app_path('Providers/AppServiceProvider.php');
        $originalProviderContent = File::get($providerPath);

        // Snapshot content of any pre-existing target (only reachable via --force)
        // so a failed run restores them instead of deleting a developer's real file.
        $originalTargetContents = [];
        foreach ($targets as $key => $path) {
            if (File::exists($path)) {
                $originalTargetContents[$key] = File::get($path);
            }
        }

        try {
            $this->writeMigration($targets['migration'], $names, $fields, $relations, $softDeletes);
            $this->writeModel($targets['model'], $names, $fields, $relations, $softDeletes);
            $this->writeRepositoryInterface($targets['repositoryInterface'], $names);
            $this->writeRepository($targets['repository'], $names, $relations);
            $this->writeService($targets['service'], $names, $fields, $relations);
            $this->writeStoreRequest($targets['storeRequest'], $names, $fields, $relations);
            $this->writeUpdateRequest($targets['updateRequest'], $names, $fields, $relations);
            $this->writeResource($targets['resource'], $names, $fields, $relations, $softDeletes);
            $this->writeController($targets['controller'], $names, $moduleName);
            $this->writeRoutes($targets['routes'], $names);

            if ($wantsSeeder) {
                $this->writeSeeder($targets['seeder'], $names, $fields, $relations);
            }

            $this->insertRepositoryBinding($names);

            $this->info('Module generated:');
            foreach ($targets as $key => $path) {
                $this->line("  - {$path}");
            }
            $this->line("  - {$providerPath} (repository binding inserted)");

            $this->printPermissionSnippet($moduleName);

            $this->newLine();
            $this->info('Running php artisan migrate --pretend to validate the new migration...');
            $exitCode = $this->call('migrate', ['--pretend' => true]);
            if ($exitCode !== self::SUCCESS) {
                throw new \RuntimeException('migrate --pretend reported an error.');
            }
        } catch (\Throwable $e) {
            $this->rollback($targets, $originalTargetContents, $providerPath, $originalProviderContent);
            $this->error("Generation failed and was rolled back: {$e->getMessage()}");
            $this->line('Fix the schema and re-run — no partial files were left behind.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Restores every target to its pre-run state: deletes files that didn't exist
     * before this run, restores original content for pre-existing files touched via
     * --force, and reverts AppServiceProvider.php — so a failed run never leaves a
     * half-generated module or a dangling repository binding behind.
     */
    private function rollback(array $targets, array $originalTargetContents, string $providerPath, string $originalProviderContent): void
    {
        foreach ($targets as $key => $path) {
            if (isset($originalTargetContents[$key])) {
                File::put($path, $originalTargetContents[$key]);
            } elseif (File::exists($path)) {
                File::delete($path);
            }
        }

        File::put($providerPath, $originalProviderContent);
    }

    /**
     * @return array{studly:string,studlyPlural:string,camel:string,snake:string,table:string,kebabPlural:string}
     */
    private function deriveNames(string $rawName): array
    {
        $studly = Str::studly(Str::singular($rawName));

        return [
            'studly' => $studly,
            'studlyPlural' => Str::pluralStudly($studly),
            'camel' => Str::camel($studly),
            'snake' => Str::snake($studly),
            'table' => Str::snake(Str::pluralStudly($studly)),
            'kebabPlural' => Str::kebab(Str::pluralStudly($studly)),
        ];
    }

    private function resolveSchemaPath(string $studlyName): string
    {
        $option = $this->option('schema');
        if ($option) {
            return base_path($option);
        }

        return base_path("module-schemas/{$studlyName}.yaml");
    }

    /**
     * @return array{migration:string,model:string,repositoryInterface:string,repository:string,service:string,storeRequest:string,updateRequest:string,resource:string,controller:string,routes:string,seeder?:string}
     */
    private function resolveTargets(array $names, bool $wantsSeeder): array
    {
        $timestamp = date('Y_m_d_His');

        $targets = [
            'migration' => database_path("migrations/{$timestamp}_create_{$names['table']}_table.php"),
            'model' => app_path("Models/{$names['studly']}.php"),
            'repositoryInterface' => app_path("Contracts/Repositories/{$names['studly']}RepositoryInterface.php"),
            'repository' => app_path("Repositories/{$names['studly']}RepositoryEloquent.php"),
            'service' => app_path("Services/{$names['studly']}Service.php"),
            'storeRequest' => app_path("Http/Requests/{$names['studlyPlural']}/Create{$names['studly']}Request.php"),
            'updateRequest' => app_path("Http/Requests/{$names['studlyPlural']}/Update{$names['studly']}Request.php"),
            'resource' => app_path("Http/Resources/V1/{$names['studly']}Resource.php"),
            'controller' => app_path("Http/Controllers/V1/{$names['studly']}Controller.php"),
            'routes' => base_path("routes/api/{$names['kebabPlural']}.php"),
        ];

        if ($wantsSeeder) {
            $targets['seeder'] = database_path("seeders/{$names['studly']}Seeder.php");
        }

        // The migration filename embeds a timestamp, so it will never collide with an
        // existing file on disk — check by table-name pattern instead so --force /
        // conflict detection still means something for migrations.
        $existingMigration = collect(File::glob(database_path('migrations/*_create_'.$names['table'].'_table.php')))->first();
        if ($existingMigration) {
            $targets['migration'] = $existingMigration;
        }

        return $targets;
    }

    /**
     * Validates raw YAML field/relation entries before anything is normalized or
     * interpolated into generated code: shape (must be a mapping), required keys,
     * safe identifiers (blocks path traversal / PHP injection via a crafted name),
     * and a well-formed decimal:P,S precision/scale. Returns an error message, or
     * null when the schema is safe to generate from.
     */
    private function validateSchemaShape(array $rawFields, array $rawRelations): ?string
    {
        foreach ($rawFields as $index => $field) {
            if (! is_array($field) || ! isset($field['name'], $field['type'])) {
                return "Field #{$index} in the schema must be a mapping with at least 'name' and 'type'.";
            }

            if (! preg_match(self::IDENTIFIER_PATTERN, (string) $field['name'])) {
                return "Invalid field name '{$field['name']}'. Use letters, digits or '_' only, starting with a letter.";
            }

            $type = (string) $field['type'];
            if (str_starts_with($type, 'decimal:')) {
                if (! preg_match(self::DECIMAL_TYPE_PATTERN, $type)) {
                    return "Invalid decimal type '{$type}' for field '{$field['name']}'. Expected format: decimal:P,S (e.g. decimal:8,2).";
                }
            } elseif (! in_array($type, self::FIELD_TYPES, true)) {
                return "Unsupported field type '{$type}' for field '{$field['name']}'. Supported: ".implode(', ', self::FIELD_TYPES).', decimal:P,S';
            }
        }

        foreach ($rawRelations as $index => $relation) {
            if (! is_array($relation) || ! isset($relation['name'], $relation['type'], $relation['model'])) {
                return "Relation #{$index} in the schema must be a mapping with 'name', 'type' and 'model'.";
            }

            if (! preg_match(self::IDENTIFIER_PATTERN, (string) $relation['name'])) {
                return "Invalid relation name '{$relation['name']}'. Use letters, digits or '_' only, starting with a letter.";
            }

            if (! preg_match(self::IDENTIFIER_PATTERN, (string) $relation['model'])) {
                return "Invalid relation model '{$relation['model']}' for relation '{$relation['name']}'. Must be a valid class name.";
            }

            if (! in_array($relation['type'], self::RELATION_TYPES, true)) {
                return "Unsupported relation type '{$relation['type']}' for relation '{$relation['name']}'. Supported: ".implode(', ', self::RELATION_TYPES);
            }
        }

        return null;
    }

    private function normalizeFields(array $rawFields): array
    {
        return array_map(function (array $field) {
            return [
                'name' => $field['name'],
                'type' => (string) $field['type'],
                'nullable' => (bool) ($field['nullable'] ?? false),
            ];
        }, $rawFields);
    }

    private function normalizeRelations(array $rawRelations): array
    {
        return array_map(function (array $relation) {
            return [
                'name' => $relation['name'],
                'type' => (string) $relation['type'],
                'model' => (string) $relation['model'],
                'nullable' => (bool) ($relation['nullable'] ?? false),
            ];
        }, $rawRelations);
    }

    private function belongsToRelations(array $relations): array
    {
        return array_values(array_filter($relations, fn (array $r) => $r['type'] === 'belongsTo'));
    }

    private function otherRelations(array $relations): array
    {
        return array_values(array_filter($relations, fn (array $r) => $r['type'] !== 'belongsTo'));
    }

    private function render(string $stubName, array $tokens): string
    {
        $stub = File::get(base_path("stubs/module/{$stubName}.stub"));

        $search = array_map(fn (string $key) => '{{ '.$key.' }}', array_keys($tokens));

        return str_replace($search, array_values($tokens), $stub);
    }

    private function put(string $path, string $content): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }

    // -- Migration -----------------------------------------------------

    private function migrationColumnLine(array $field): string
    {
        $name = $field['name'];
        $nullable = $field['nullable'] ? '->nullable()' : '';

        if (str_starts_with($field['type'], 'decimal:')) {
            [$precision, $scale] = explode(',', substr($field['type'], strlen('decimal:')));

            return "            \$table->decimal('{$name}', {$precision}, {$scale}){$nullable};";
        }

        $columnMethod = match ($field['type']) {
            'string' => 'string',
            'text' => 'text',
            'integer' => 'integer',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'dateTime',
            default => 'string',
        };

        return "            \$table->{$columnMethod}('{$name}'){$nullable};";
    }

    private function migrationRelationLine(array $relation): string
    {
        $column = Str::snake($relation['name']).'_id';
        $table = Str::snake(Str::pluralStudly($relation['model']));
        $nullable = $relation['nullable'] ? '->nullable()' : '';
        $onDelete = $relation['nullable'] ? 'nullOnDelete()' : 'cascadeOnDelete()';

        return "            \$table->foreignId('{$column}'){$nullable}->constrained('{$table}')->{$onDelete};";
    }

    private function writeMigration(string $path, array $names, array $fields, array $relations, bool $softDeletes): void
    {
        $lines = [];
        foreach ($fields as $field) {
            $lines[] = $this->migrationColumnLine($field);
        }
        foreach ($this->belongsToRelations($relations) as $relation) {
            $lines[] = $this->migrationRelationLine($relation);
        }

        $content = $this->render('migration', [
            'tableName' => $names['table'],
            'migrationColumns' => implode("\n", $lines),
            'migrationSoftDeletes' => $softDeletes ? '            $table->softDeletes();' : '',
        ]);

        $this->put($path, $content);
    }

    // -- Model -----------------------------------------------------

    private function writeModel(string $path, array $names, array $fields, array $relations, bool $softDeletes): void
    {
        $belongsTo = $this->belongsToRelations($relations);
        $others = $this->otherRelations($relations);

        $useLines = [];
        $relationImports = [];
        foreach ($belongsTo as $relation) {
            $relationImports['BelongsTo'] = 'use Illuminate\Database\Eloquent\Relations\BelongsTo;';
        }
        foreach ($others as $relation) {
            $relationImports[$relation['type']] = 'use Illuminate\Database\Eloquent\Relations\\'.ucfirst($relation['type']).';';
        }
        ksort($relationImports);
        $useLines = array_values($relationImports);
        if ($softDeletes) {
            $useLines[] = 'use Illuminate\Database\Eloquent\SoftDeletes;';
        }
        sort($useLines);

        $fillable = array_map(fn (array $field) => "        '{$field['name']}',", $fields);
        foreach ($belongsTo as $relation) {
            $fillable[] = "        '".Str::snake($relation['name'])."_id',";
        }

        $casts = [];
        foreach ($fields as $field) {
            $cast = match (true) {
                str_starts_with($field['type'], 'decimal:') => "'decimal:".explode(',', substr($field['type'], strlen('decimal:')))[1]."'",
                $field['type'] === 'integer' => "'integer'",
                $field['type'] === 'boolean' => "'boolean'",
                $field['type'] === 'date' => "'date'",
                $field['type'] === 'datetime' => "'datetime'",
                default => null,
            };
            if ($cast !== null) {
                $casts[] = "        '{$field['name']}' => {$cast},";
            }
        }

        $body = '';
        if (! empty($casts)) {
            $body .= "\n    protected \$casts = [\n".implode("\n", $casts)."\n    ];\n";
        }
        foreach ($belongsTo as $relation) {
            $method = Str::camel($relation['name']);
            $body .= "\n    public function {$method}(): BelongsTo\n    {\n        return \$this->belongsTo({$relation['model']}::class);\n    }\n";
        }
        foreach ($others as $relation) {
            $method = Str::camel($relation['name']);
            $returnType = ucfirst($relation['type']);
            $call = match ($relation['type']) {
                'hasMany' => "hasMany({$relation['model']}::class)",
                'hasOne' => "hasOne({$relation['model']}::class)",
                'belongsToMany' => "belongsToMany({$relation['model']}::class)",
                default => '',
            };
            $body .= "\n    public function {$method}(): {$returnType}\n    {\n        return \$this->{$call};\n    }\n";
        }

        $content = $this->render('model', [
            'modelUseStatements' => empty($useLines) ? '' : implode("\n", $useLines)."\n",
            'studlyName' => $names['studly'],
            'modelTraits' => $softDeletes ? ', SoftDeletes' : '',
            'modelFillable' => implode("\n", $fillable),
            'modelBody' => $body,
        ]);

        $this->put($path, $content);
    }

    // -- Repository interface / implementation ---------------------

    private function writeRepositoryInterface(string $path, array $names): void
    {
        $content = $this->render('repository-interface', [
            'studlyName' => $names['studly'],
        ]);

        $this->put($path, $content);
    }

    private function writeRepository(string $path, array $names, array $relations): void
    {
        $belongsTo = $this->belongsToRelations($relations);
        $relationNames = array_map(fn (array $r) => "'".Str::camel($r['name'])."'", $belongsTo);

        $withRelationsCall = empty($relationNames) ? '' : '->with(['.implode(', ', $relationNames).'])';
        $freshCall = empty($relationNames) ? '$model->fresh()' : '$model->fresh(['.implode(', ', $relationNames).'])';

        $content = $this->render('repository', [
            'studlyName' => $names['studly'],
            'withRelationsCall' => $withRelationsCall,
            'freshCall' => $freshCall,
        ]);

        $this->put($path, $content);
    }

    // -- Service -----------------------------------------------------

    private function writeService(string $path, array $names, array $fields, array $relations): void
    {
        $belongsTo = $this->belongsToRelations($relations);

        $searchColumn = 'name';
        foreach ($fields as $field) {
            if (in_array($field['type'], ['string', 'text'], true)) {
                $searchColumn = $field['name'];
                break;
            }
        }

        $serviceUseStatements = array_unique(array_map(fn (array $r) => "use App\\Models\\{$r['model']};", $belongsTo));

        $fieldNames = array_map(fn (array $field) => $field['name'], $fields);

        $createLines = [];
        $createLines[] = '        $payload = [';
        foreach ($fields as $field) {
            $createLines[] = "            '{$field['name']}' => \$data['{$field['name']}'] ?? null,";
        }
        $createLines[] = '        ];';
        foreach ($belongsTo as $relation) {
            $guidKey = Str::snake($relation['name']).'_guid';
            $idKey = Str::snake($relation['name']).'_id';
            if ($relation['nullable']) {
                $createLines[] = '';
                $createLines[] = "        if (isset(\$data['{$guidKey}'])) {";
                $createLines[] = "            \$payload['{$idKey}'] = {$relation['model']}::where('guid', \$data['{$guidKey}'])->firstOrFail()->id;";
                $createLines[] = '        }';
            } else {
                $createLines[] = "        \$payload['{$idKey}'] = {$relation['model']}::where('guid', \$data['{$guidKey}'])->firstOrFail()->id;";
            }
        }
        $createLines[] = '';
        $createLines[] = "        return \$this->{$names['camel']}Repository->create(\$payload);";

        $updateLines = [];
        $flatFieldList = implode(', ', array_map(fn ($n) => "'{$n}'", $fieldNames));
        $updateLines[] = "        \$payload = array_intersect_key(\$data, array_flip([{$flatFieldList}]));";
        foreach ($belongsTo as $relation) {
            $guidKey = Str::snake($relation['name']).'_guid';
            $idKey = Str::snake($relation['name']).'_id';
            $updateLines[] = '';
            $updateLines[] = "        if (isset(\$data['{$guidKey}'])) {";
            $updateLines[] = "            \$payload['{$idKey}'] = {$relation['model']}::where('guid', \$data['{$guidKey}'])->firstOrFail()->id;";
            $updateLines[] = '        }';
        }
        $updateLines[] = '';
        $updateLines[] = "        return \$this->{$names['camel']}Repository->update(\${$names['camel']}, \$payload);";

        $content = $this->render('service', [
            'studlyName' => $names['studly'],
            'camelName' => $names['camel'],
            'serviceUseStatements' => empty($serviceUseStatements) ? '' : implode("\n", $serviceUseStatements)."\n",
            'searchColumn' => $searchColumn,
            'serviceCreateBody' => implode("\n", $createLines),
            'serviceUpdateBody' => implode("\n", $updateLines),
        ]);

        $this->put($path, $content);
    }

    // -- Form Requests -----------------------------------------------

    private function fieldRule(array $field): string
    {
        $prefix = $field['nullable'] ? 'nullable' : 'required';

        $type = match (true) {
            str_starts_with($field['type'], 'decimal:') => 'numeric',
            $field['type'] === 'integer' => 'integer',
            $field['type'] === 'boolean' => 'boolean',
            $field['type'] === 'date' => 'date',
            $field['type'] === 'datetime' => 'date',
            default => 'string',
        };

        return "['{$prefix}', '{$type}']";
    }

    private function requestRulesAndMessages(array $fields, array $relations): array
    {
        $rules = [];
        $messages = [];

        foreach ($fields as $field) {
            $rules[] = "            '{$field['name']}' => {$this->fieldRule($field)},";

            $requiredLabel = $field['nullable'] ? null : 'required';
            if ($requiredLabel) {
                $messages[] = "            '{$field['name']}.required' => 'El campo {$field['name']} es obligatorio.',";
            }
        }

        foreach ($relations as $relation) {
            if ($relation['type'] !== 'belongsTo') {
                continue;
            }

            $guidKey = Str::snake($relation['name']).'_guid';
            $table = Str::snake(Str::pluralStudly($relation['model']));
            $prefix = $relation['nullable'] ? 'nullable' : 'required';

            $rules[] = "            '{$guidKey}' => ['{$prefix}', 'uuid', 'exists:{$table},guid'],";

            if (! $relation['nullable']) {
                $messages[] = "            '{$guidKey}.required' => 'El campo {$guidKey} es obligatorio.',";
            }
            $messages[] = "            '{$guidKey}.exists' => 'El valor de {$guidKey} no es válido.',";
        }

        return [$rules, $messages];
    }

    private function writeStoreRequest(string $path, array $names, array $fields, array $relations): void
    {
        [$rules, $messages] = $this->requestRulesAndMessages($fields, $relations);

        $content = $this->render('store-request', [
            'studlyPlural' => $names['studlyPlural'],
            'studlyName' => $names['studly'],
            'storeRules' => implode("\n", $rules),
            'storeMessages' => implode("\n", $messages),
        ]);

        $this->put($path, $content);
    }

    private function writeUpdateRequest(string $path, array $names, array $fields, array $relations): void
    {
        // Update requests treat every field as optional (partial updates) — swap
        // 'required' for 'sometimes' but keep the same value/type constraints.
        $updateFields = array_map(function (array $field) {
            $field['nullable'] = true;

            return $field;
        }, $fields);
        $updateRelations = array_map(function (array $relation) {
            $relation['nullable'] = true;

            return $relation;
        }, $relations);

        [$rules, $messages] = $this->requestRulesAndMessages($updateFields, $updateRelations);

        $content = $this->render('update-request', [
            'studlyPlural' => $names['studlyPlural'],
            'studlyName' => $names['studly'],
            'updateRules' => implode("\n", $rules),
            'updateMessages' => implode("\n", $messages),
        ]);

        $this->put($path, $content);
    }

    // -- Resource -----------------------------------------------------

    private function writeResource(string $path, array $names, array $fields, array $relations, bool $softDeletes): void
    {
        $lines = [];
        foreach ($fields as $field) {
            $accessor = in_array($field['type'], ['date', 'datetime'], true)
                ? "\$this->{$field['name']}?->toISOString()"
                : "\$this->{$field['name']}";
            $lines[] = "            '{$field['name']}' => {$accessor},";
        }

        foreach ($this->belongsToRelations($relations) as $relation) {
            $method = Str::camel($relation['name']);
            $guidKey = Str::snake($relation['name']).'_guid';
            $lines[] = "            '{$guidKey}' => \$this->{$method}?->guid,";
        }

        $content = $this->render('resource', [
            'studlyName' => $names['studly'],
            'resourceFields' => implode("\n", $lines),
            'resourceSoftDeleteField' => $softDeletes ? "            'deleted_at' => \$this->deleted_at?->toISOString()," : '',
        ]);

        $this->put($path, $content);
    }

    // -- Controller & routes -------------------------------------------

    private function writeController(string $path, array $names, string $moduleName): void
    {
        $content = $this->render('controller', [
            'studlyPlural' => $names['studlyPlural'],
            'studlyName' => $names['studly'],
            'camelName' => $names['camel'],
            'moduleName' => $moduleName,
        ]);

        $this->put($path, $content);
    }

    private function writeRoutes(string $path, array $names): void
    {
        $content = $this->render('routes', [
            'studlyName' => $names['studly'],
            'kebabPlural' => $names['kebabPlural'],
        ]);

        $this->put($path, $content);
    }

    // -- Seeder (optional) ---------------------------------------------

    private function writeSeeder(string $path, array $names, array $fields, array $relations): void
    {
        $lines = array_map(fn (array $field) => "                '{$field['name']}' => null,", $fields);

        foreach ($this->belongsToRelations($relations) as $relation) {
            $idKey = Str::snake($relation['name']).'_id';
            $requiredNote = $relation['nullable'] ? '' : ' — required, seeder will fail until this is set';
            $lines[] = "                '{$idKey}' => null, // TODO: set a real {$relation['model']} id{$requiredNote}";
        }

        $content = <<<PHP
        <?php

        namespace Database\Seeders;

        use App\Models\\{$names['studly']};
        use Illuminate\Database\Console\Seeds\WithoutModelEvents;
        use Illuminate\Database\Seeder;
        use Illuminate\Support\Str;

        class {$names['studly']}Seeder extends Seeder
        {
            use WithoutModelEvents;

            public function run(): void
            {
                // Fill in real values below — placeholders are left null on purpose.
                {$names['studly']}::create([
                    'guid' => Str::uuid()->toString(),
        {$this->indentLines($lines)}
                ]);
            }
        }

        PHP;

        $this->put($path, $this->stripIndent($content));

        $this->line("Note: register {$names['studly']}Seeder::class in database/seeders/DatabaseSeeder.php manually if you want it to run automatically.");
    }

    private function indentLines(array $lines): string
    {
        return implode("\n", $lines);
    }

    private function stripIndent(string $content): string
    {
        // The heredoc above is indented to match surrounding code style; strip the
        // common 8-space leading indentation so the written file starts at column 0.
        $lines = explode("\n", $content);
        $stripped = array_map(function (string $line) {
            return str_starts_with($line, '        ') ? substr($line, 8) : $line;
        }, $lines);

        return implode("\n", $stripped);
    }

    // -- AppServiceProvider binding -------------------------------------

    private function insertRepositoryBinding(array $names): void
    {
        $path = app_path('Providers/AppServiceProvider.php');
        $content = File::get($path);

        $interfaceFqn = "App\\Contracts\\Repositories\\{$names['studly']}RepositoryInterface";
        $eloquentFqn = "App\\Repositories\\{$names['studly']}RepositoryEloquent";
        $anchor = '// module-generator:repository-bindings — do not remove';

        if (! str_contains($content, $anchor)) {
            $this->warn('Anchor comment not found in AppServiceProvider::register() — skipping automatic binding insertion.');
            $this->line("Add this manually inside register(): \$this->app->bind(\\{$interfaceFqn}::class, \\{$eloquentFqn}::class);");

            return;
        }

        if (str_contains($content, "bind({$names['studly']}RepositoryInterface::class")) {
            $this->line("Binding for {$names['studly']}RepositoryInterface already present — skipping.");

            return;
        }

        $binding = "\$this->app->bind({$names['studly']}RepositoryInterface::class, {$names['studly']}RepositoryEloquent::class);\n        {$anchor}";
        $content = str_replace($anchor, $binding, $content);

        $content = $this->addUseStatementIfMissing($content, "use {$interfaceFqn};");
        $content = $this->addUseStatementIfMissing($content, "use {$eloquentFqn};");

        File::put($path, $content);
    }

    private function addUseStatementIfMissing(string $content, string $useStatement): string
    {
        if (str_contains($content, $useStatement)) {
            return $content;
        }

        // Insert after the last existing "use App\...;" import line, preserving
        // alphabetical grouping style already used in this file.
        $lines = explode("\n", $content);
        $lastUseIndex = null;
        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), 'use ')) {
                $lastUseIndex = $index;
            }
        }

        if ($lastUseIndex === null) {
            return $content;
        }

        array_splice($lines, $lastUseIndex + 1, 0, [$useStatement]);

        return implode("\n", $lines);
    }

    // -- Permission snippet ---------------------------------------------

    private function printPermissionSnippet(string $moduleName): void
    {
        $this->newLine();
        $this->info('Paste into database/seeders/PermissionSeeder.php ($permissions array):');
        $this->line("            '{$moduleName}.read',");
        $this->line("            '{$moduleName}.create',");
        $this->line("            '{$moduleName}.update',");
        $this->line("            '{$moduleName}.delete',");

        $this->newLine();
        $this->info('Paste into database/seeders/RoleSeeder.php (assign to the appropriate role(s) syncPermissions() lists):');
        $this->line("            '{$moduleName}.read',");
        $this->line("            '{$moduleName}.create',");
        $this->line("            '{$moduleName}.update',");
        $this->line("            '{$moduleName}.delete',");
    }
}
