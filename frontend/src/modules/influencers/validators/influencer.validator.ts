import { z } from 'zod'
import { VIDEO_FORMATOS } from '../types/influencer.enums'

const nullableString = z.string().nullish().or(z.literal(''))

export const influencerSchema = z.object({
  // Identidad
  nombre: z.string().min(1, 'El nombre es requerido').max(150, 'Máximo 150 caracteres'),
  alias: z.string().max(150, 'Máximo 150 caracteres').nullish().or(z.literal('')),
  edad: z.number().int().min(0).max(150).nullish(),
  ciudad: z.string().max(150).nullish().or(z.literal('')),
  barrio: z.string().max(150).nullish().or(z.literal('')),
  especialidad: z.string().max(255).nullish().or(z.literal('')),
  descripcion_corta: z.string().max(255).nullish().or(z.literal('')),
  activo: z.boolean().optional(),

  // Personalidad
  backstory: nullableString,
  personalidad: nullableString,
  tono_voz: nullableString,
  regla_principal: nullableString,
  frases_positivas: z.array(z.string().max(500)).optional(),
  frases_negativas: z.array(z.string().max(500)).optional(),
  llamados_accion: z.array(z.string().max(500)).optional(),

  // Estética visual
  estetica_visual: nullableString,
  vestuario: nullableString,
  referencia_fisica: nullableString,

  // Video / Seedance
  prompt_base_seedance: nullableString,
  negative_prompt: nullableString,
  video_formato: z.enum(VIDEO_FORMATOS, { message: 'Formato de video inválido' }).nullish().or(z.literal('')),
  video_duracion_seg: z.number().int().min(1).max(600).nullish(),

  // Operativo
  resumen_ia: nullableString,
})

export type InfluencerForm = z.infer<typeof influencerSchema>
