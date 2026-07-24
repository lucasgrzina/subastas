import { z } from 'zod'

export const auctionSchema = z
  .object({
    title: z.string().min(1, 'El título es requerido').max(255, 'Máximo 255 caracteres'),
    description: z.string().max(5000).optional().nullable(),
    starts_at: z.string().min(1, 'La fecha de inicio es requerida'),
    ends_at: z.string().optional().nullable(),
    status: z.enum(['draft', 'scheduled', 'live', 'closed', 'cancelled']).optional(),
  })
  .refine(
    (values) => !values.ends_at || !values.starts_at || new Date(values.ends_at) > new Date(values.starts_at),
    { message: 'La fecha de fin debe ser posterior a la fecha de inicio', path: ['ends_at'] },
  )

export type AuctionFormValues = z.infer<typeof auctionSchema>
