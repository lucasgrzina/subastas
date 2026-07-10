export interface Influencer {
  guid: string

  // Identidad
  nombre: string
  alias: string | null
  edad: number | null
  ciudad: string | null
  barrio: string | null
  especialidad: string | null
  descripcion_corta: string | null
  character_image_url: string | null
  character_image_full_url: string | null
  activo: boolean

  // Personalidad
  backstory: string | null
  personalidad: string | null
  tono_voz: string | null
  regla_principal: string | null
  frases_positivas: string[]
  frases_negativas: string[]
  llamados_accion: string[]

  // Estética visual
  estetica_visual: string | null
  vestuario: string | null
  referencia_fisica: string | null

  // Video / Seedance
  prompt_base_seedance: string | null
  negative_prompt: string | null
  video_formato: string | null
  video_duracion_seg: number | null

  // Operativo
  resumen_ia: string | null

  created_at: string
  updated_at: string
}

export type InfluencerItem = Influencer

export interface InfluencerListParams {
  search?: string
  activo?: boolean | ''
  page?: number
  per_page?: number
}

export interface InfluencerListResponse {
  data: InfluencerItem[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface InfluencerPayload {
  nombre: string
  alias?: string | null
  edad?: number | null
  ciudad?: string | null
  barrio?: string | null
  especialidad?: string | null
  descripcion_corta?: string | null
  activo?: boolean
  backstory?: string | null
  personalidad?: string | null
  tono_voz?: string | null
  regla_principal?: string | null
  frases_positivas?: string[]
  frases_negativas?: string[]
  llamados_accion?: string[]
  estetica_visual?: string | null
  vestuario?: string | null
  referencia_fisica?: string | null
  prompt_base_seedance?: string | null
  negative_prompt?: string | null
  video_formato?: string | null
  video_duracion_seg?: number | null
  resumen_ia?: string | null
  // Imagen (subida diferida)
  image_token?: string | null
  remove_image?: boolean
}

export interface InfluencerFilters {
  search?: string
  activo?: boolean | ''
  page?: number
  per_page?: number
}
