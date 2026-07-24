<?php

namespace App\Providers;

use App\Contracts\Exports\ExportResolverInterface;
use App\Contracts\Repositories\AuctionRepositoryInterface;
use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Contracts\Repositories\ExportRepositoryInterface;
use App\Contracts\Repositories\LotRepositoryInterface;
use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Contracts\Repositories\PermissionRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Contracts\Repositories\SupportMessageReplyRepositoryInterface;
use App\Contracts\Repositories\SupportMessageRepositoryInterface;
use App\Contracts\Repositories\SystemSettingRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\Export;
use App\Repositories\AuctionRepositoryEloquent;
use App\Repositories\CurrencyRepositoryEloquent;
use App\Repositories\ExportRepositoryEloquent;
use App\Repositories\LotRepositoryEloquent;
use App\Repositories\NotificationRepositoryEloquent;
use App\Repositories\PermissionRepositoryEloquent;
use App\Repositories\ProductRepositoryEloquent;
use App\Repositories\RoleRepositoryEloquent;
use App\Repositories\SupportMessageReplyRepositoryEloquent;
use App\Repositories\SupportMessageRepositoryEloquent;
use App\Repositories\SystemSettingRepositoryEloquent;
use App\Repositories\UserRepositoryEloquent;
use App\Services\Exports\ExportResolverService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepositoryEloquent::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepositoryEloquent::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepositoryEloquent::class);
        $this->app->bind(ExportRepositoryInterface::class, ExportRepositoryEloquent::class);
        $this->app->bind(SupportMessageRepositoryInterface::class, SupportMessageRepositoryEloquent::class);
        $this->app->bind(SupportMessageReplyRepositoryInterface::class, SupportMessageReplyRepositoryEloquent::class);
        $this->app->bind(ExportResolverInterface::class, ExportResolverService::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepositoryEloquent::class);
        $this->app->bind(SystemSettingRepositoryInterface::class, SystemSettingRepositoryEloquent::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepositoryEloquent::class);
        $this->app->bind(AuctionRepositoryInterface::class, AuctionRepositoryEloquent::class);
        $this->app->bind(LotRepositoryInterface::class, LotRepositoryEloquent::class);
        $this->app->bind(CurrencyRepositoryInterface::class, CurrencyRepositoryEloquent::class);
        // module-generator:repository-bindings — do not remove
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email', '').$request->ip());
        });

        Gate::policy(Export::class, ExportPolicy::class);
    }
}
