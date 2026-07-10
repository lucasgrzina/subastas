import { z } from 'zod'

export const apiClientCreateSchema = z.object({
  nombre: z
    .string()
    .min(1, 'El nombre es requerido')
    .max(150, 'Máximo 150 caracteres'),
  email: z
    .string()
    .email('Email inválido')
    .max(255, 'Máximo 255 caracteres')
    .optional()
    .or(z.literal('')),
})

export const apiClientUpdateSchema = apiClientCreateSchema

export type ApiClientCreateForm = z.infer<typeof apiClientCreateSchema>
export type ApiClientUpdateForm = z.infer<typeof apiClientUpdateSchema>
