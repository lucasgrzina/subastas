import { z } from 'zod'

export const createCurrencySchema = z.object({
  code: z.string().min(1, 'El campo Código es requerido').max(255, 'Máximo 255 caracteres'),
  name: z.string().min(1, 'El campo Nombre es requerido').max(255, 'Máximo 255 caracteres'),
  symbol: z.string().min(1, 'El campo Símbolo es requerido').max(255, 'Máximo 255 caracteres'),
  is_active: z.boolean(),
})

export const updateCurrencySchema = z.object({
  code: z.string().min(1, 'El campo Código es requerido').max(255, 'Máximo 255 caracteres').optional(),
  name: z.string().min(1, 'El campo Nombre es requerido').max(255, 'Máximo 255 caracteres').optional(),
  symbol: z.string().min(1, 'El campo Símbolo es requerido').max(255, 'Máximo 255 caracteres').optional(),
  is_active: z.boolean().optional(),
})

export type CurrencyFormValues = z.infer<typeof createCurrencySchema>
export type CurrencyUpdateFormValues = z.infer<typeof updateCurrencySchema>
