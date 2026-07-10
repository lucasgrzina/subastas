<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { influencerSchema } from '../../validators/influencer.validator'
import { VIDEO_FORMATO_OPTIONS } from '../../types/influencer.enums'
import PhraseListEditor from '../PhraseListEditor.vue'
import InfluencerImageUploader from '../InfluencerImageUploader.vue'
import type { InfluencerItem, InfluencerPayload } from '../../types/influencer.types'

const props = withDefaults(
  defineProps<{
    mode: 'create' | 'edit'
    initialValues?: InfluencerItem | null
    fieldErrors?: Record<string, string> | null
  }>(),
  {},
)

const emit = defineEmits<{
  submit: [values: InfluencerPayload]
}>()

const { errors, defineField, handleSubmit, setErrors, setValues, values } = useForm({
  validationSchema: toTypedSchema(influencerSchema),
  initialValues: {
    frases_positivas: [],
    frases_negativas: [],
    llamados_accion: [],
    activo: true,
  },
})

// Identidad
const [nombre, nombreAttrs] = defineField('nombre')
const [alias, aliasAttrs] = defineField('alias')
const [edad, edadAttrs] = defineField('edad')
const [ciudad, ciudadAttrs] = defineField('ciudad')
const [barrio, barrioAttrs] = defineField('barrio')
const [especialidad, especialidadAttrs] = defineField('especialidad')
const [descripcionCorta, descripcionCortaAttrs] = defineField('descripcion_corta')
const [activo] = defineField('activo')

// Personalidad
const [backstory, backstoryAttrs] = defineField('backstory')
const [personalidad, personalidadAttrs] = defineField('personalidad')
const [tonoVoz, tonoVozAttrs] = defineField('tono_voz')
const [reglaPrincipal, reglaPrincipalAttrs] = defineField('regla_principal')
const [frasesPositivas] = defineField('frases_positivas')
const [frasesNegativas] = defineField('frases_negativas')
const [llamadosAccion] = defineField('llamados_accion')

// Estética
const [esteticaVisual, esteticaVisualAttrs] = defineField('estetica_visual')
const [vestuario, vestuarioAttrs] = defineField('vestuario')
const [referenciaFisica, referenciaFisicaAttrs] = defineField('referencia_fisica')

// Video
const [promptBaseSeedance, promptBaseSeedanceAttrs] = defineField('prompt_base_seedance')
const [negativePrompt, negativePromptAttrs] = defineField('negative_prompt')
const [videoFormato] = defineField('video_formato')
const [videoDuracionSeg, videoDuracionSegAttrs] = defineField('video_duracion_seg')

// Operativo
const [resumenIa, resumenIaAttrs] = defineField('resumen_ia')

// Imagen (subida diferida): token temporal + flag de removido
const imageToken = ref<string | null>(null)
const removeImage = ref(false)

watch(
  () => props.initialValues,
  (vals) => {
    if (props.mode === 'edit' && vals) {
      setValues({
        nombre: vals.nombre ?? '',
        alias: vals.alias ?? '',
        edad: vals.edad ?? undefined,
        ciudad: vals.ciudad ?? '',
        barrio: vals.barrio ?? '',
        especialidad: vals.especialidad ?? '',
        descripcion_corta: vals.descripcion_corta ?? '',
        activo: vals.activo,
        backstory: vals.backstory ?? '',
        personalidad: vals.personalidad ?? '',
        tono_voz: vals.tono_voz ?? '',
        regla_principal: vals.regla_principal ?? '',
        frases_positivas: vals.frases_positivas ?? [],
        frases_negativas: vals.frases_negativas ?? [],
        llamados_accion: vals.llamados_accion ?? [],
        estetica_visual: vals.estetica_visual ?? '',
        vestuario: vals.vestuario ?? '',
        referencia_fisica: vals.referencia_fisica ?? '',
        prompt_base_seedance: vals.prompt_base_seedance ?? '',
        negative_prompt: vals.negative_prompt ?? '',
        video_formato: vals.video_formato ?? '',
        video_duracion_seg: vals.video_duracion_seg ?? undefined,
        resumen_ia: vals.resumen_ia ?? '',
      })
    }
  },
  { immediate: true, deep: true },
)

watch(
  () => props.fieldErrors,
  (errs) => {
    setErrors(errs ?? {})
  },
)

/** Convierte '' → null/undefined para no mandar strings vacíos al backend. */
function clean<T>(v: T): T | undefined {
  return v === '' || v === null ? undefined : v
}

const hasInvalidSubmit = ref(false)

const onSubmit = handleSubmit((formValues) => {
  hasInvalidSubmit.value = false
  emit('submit', {
    nombre: formValues.nombre,
    alias: clean(formValues.alias),
    edad: clean(formValues.edad),
    ciudad: clean(formValues.ciudad),
    barrio: clean(formValues.barrio),
    especialidad: clean(formValues.especialidad),
    descripcion_corta: clean(formValues.descripcion_corta),
    activo: formValues.activo,
    backstory: clean(formValues.backstory),
    personalidad: clean(formValues.personalidad),
    tono_voz: clean(formValues.tono_voz),
    regla_principal: clean(formValues.regla_principal),
    frases_positivas: formValues.frases_positivas ?? [],
    frases_negativas: formValues.frases_negativas ?? [],
    llamados_accion: formValues.llamados_accion ?? [],
    estetica_visual: clean(formValues.estetica_visual),
    vestuario: clean(formValues.vestuario),
    referencia_fisica: clean(formValues.referencia_fisica),
    prompt_base_seedance: clean(formValues.prompt_base_seedance),
    negative_prompt: clean(formValues.negative_prompt),
    video_formato: clean(formValues.video_formato),
    video_duracion_seg: clean(formValues.video_duracion_seg),
    resumen_ia: clean(formValues.resumen_ia),
    image_token: imageToken.value ?? undefined,
    remove_image: removeImage.value ? true : undefined,
  })
}, () => {
  hasInvalidSubmit.value = true
})

defineExpose({ submit: onSubmit, values })
</script>

<template>
  <a-form layout="vertical" @submit.prevent="onSubmit">
    <a-alert
      v-if="hasInvalidSubmit"
      message="Revisá los campos marcados en rojo antes de guardar."
      type="warning"
      show-icon
      style="margin-bottom: 16px"
    />

    <a-collapse :default-active-key="['identidad']" :bordered="false">
      <!-- IDENTIDAD -->
      <a-collapse-panel key="identidad" header="Identidad">
        <a-row :gutter="16">
          <a-col flex="176px">
            <a-form-item label="Imagen de personaje">
              <InfluencerImageUploader
                :current-url="initialValues?.character_image_full_url ?? null"
                @update:token="(t) => (imageToken = t)"
                @update:removed="(r) => (removeImage = r)"
              />
            </a-form-item>
          </a-col>
          <a-col flex="auto">
            <a-row :gutter="16">
              <a-col :span="12">
                <a-form-item label="Nombre" :validate-status="errors.nombre ? 'error' : ''" :help="errors.nombre ?? ''">
                  <a-input v-model:value="nombre" v-bind="nombreAttrs" placeholder="Ej: Leandro" />
                </a-form-item>
              </a-col>
              <a-col :span="12">
                <a-form-item label="Alias" :validate-status="errors.alias ? 'error' : ''" :help="errors.alias ?? ''">
                  <a-input v-model:value="alias" v-bind="aliasAttrs" placeholder="Ej: Lean" />
                </a-form-item>
              </a-col>
            </a-row>
            <a-form-item label="Especialidad">
              <a-input v-model:value="especialidad" v-bind="especialidadAttrs" placeholder="Ej: pastas y salsas" />
            </a-form-item>
          </a-col>
        </a-row>

        <a-row :gutter="16">
          <a-col :span="8">
            <a-form-item label="Edad" :validate-status="errors.edad ? 'error' : ''" :help="errors.edad ?? ''">
              <a-input-number v-model:value="edad" v-bind="edadAttrs" style="width: 100%" :min="0" :max="150" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="Ciudad">
              <a-input v-model:value="ciudad" v-bind="ciudadAttrs" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="Barrio">
              <a-input v-model:value="barrio" v-bind="barrioAttrs" />
            </a-form-item>
          </a-col>
        </a-row>

        <a-form-item label="Descripción corta" :help="'Se muestra en cards y listados.'">
          <a-input v-model:value="descripcionCorta" v-bind="descripcionCortaAttrs" />
        </a-form-item>

        <a-form-item label="Activo">
          <a-switch v-model:checked="activo" checked-children="SI" un-checked-children="NO" />
        </a-form-item>
      </a-collapse-panel>

      <!-- PERSONALIDAD -->
      <a-collapse-panel key="personalidad" header="Personalidad">
        <a-form-item label="Backstory">
          <a-textarea v-model:value="backstory" v-bind="backstoryAttrs" :rows="3" placeholder="Origen, familia, motivaciones" />
        </a-form-item>
        <a-form-item label="Personalidad">
          <a-textarea v-model:value="personalidad" v-bind="personalidadAttrs" :rows="3" placeholder="Rasgos principales" />
        </a-form-item>
        <a-form-item label="Tono de voz">
          <a-textarea v-model:value="tonoVoz" v-bind="tonoVozAttrs" :rows="2" placeholder="Cómo habla, registros que usa" />
        </a-form-item>
        <a-form-item label="Regla principal">
          <a-textarea v-model:value="reglaPrincipal" v-bind="reglaPrincipalAttrs" :rows="2" placeholder='Ej: "la salsa siempre es protagonista"' />
        </a-form-item>

        <PhraseListEditor v-model="frasesPositivas" label="Frases positivas" placeholder="Frase característica" />
        <PhraseListEditor v-model="frasesNegativas" label="Frases negativas" placeholder="Frase que nunca dice" />
        <PhraseListEditor v-model="llamadosAccion" label="Llamados a la acción" placeholder="CTA típico" />
      </a-collapse-panel>

      <!-- ESTÉTICA -->
      <a-collapse-panel key="estetica" header="Estética visual">
        <a-form-item label="Estética visual">
          <a-textarea v-model:value="esteticaVisual" v-bind="esteticaVisualAttrs" :rows="3" placeholder="Ambiente, cocina, luz" />
        </a-form-item>
        <a-form-item label="Vestuario">
          <a-textarea v-model:value="vestuario" v-bind="vestuarioAttrs" :rows="2" placeholder="Paleta, estilo de ropa" />
        </a-form-item>
        <a-form-item label="Referencia física">
          <a-input v-model:value="referenciaFisica" v-bind="referenciaFisicaAttrs" placeholder='Ej: "Jake Gyllenhaal"' />
        </a-form-item>
      </a-collapse-panel>

      <!-- VIDEO / SEEDANCE -->
      <a-collapse-panel key="video" header="Video / Seedance">
        <a-form-item label="Prompt base (Seedance)">
          <a-textarea v-model:value="promptBaseSeedance" v-bind="promptBaseSeedanceAttrs" :rows="4" placeholder="Prompt maestro en inglés" />
        </a-form-item>
        <a-form-item label="Negative prompt">
          <a-textarea v-model:value="negativePrompt" v-bind="negativePromptAttrs" :rows="2" placeholder="Lo que Seedance debe evitar" />
        </a-form-item>
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="Formato de video" :validate-status="errors.video_formato ? 'error' : ''" :help="errors.video_formato ?? ''">
              <a-select v-model:value="videoFormato" :options="VIDEO_FORMATO_OPTIONS" allow-clear placeholder="Elegí un formato" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="Duración (seg)" :validate-status="errors.video_duracion_seg ? 'error' : ''" :help="errors.video_duracion_seg ?? ''">
              <a-input-number v-model:value="videoDuracionSeg" v-bind="videoDuracionSegAttrs" style="width: 100%" :min="1" :max="600" />
            </a-form-item>
          </a-col>
        </a-row>
      </a-collapse-panel>

      <!-- RESUMEN OPERATIVO -->
      <a-collapse-panel key="resumen" header="Resumen operativo (clave)">
        <a-form-item
          label="Resumen IA"
          :help="'Párrafo consolidado que se inyecta al prompt de Claude al generar un guion.'"
        >
          <a-textarea v-model:value="resumenIa" v-bind="resumenIaAttrs" :rows="5" placeholder="Un párrafo que resume la esencia del personaje…" />
        </a-form-item>
      </a-collapse-panel>
    </a-collapse>

    <slot name="footer" :submit="onSubmit" />
  </a-form>
</template>
