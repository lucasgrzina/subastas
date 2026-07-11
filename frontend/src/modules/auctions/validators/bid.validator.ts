import { z } from 'zod'

/** Mirrors `CreateBidRequest`'s `['required','string','numeric','gt:0','decimal:0,2']`. */
export const bidSchema = z.object({
  amount: z
    .string()
    .min(1, 'El monto de la oferta es requerido')
    .regex(/^\d+(\.\d{1,2})?$/, 'Usá hasta 2 decimales')
    .refine((v) => Number(v) > 0, 'El monto debe ser mayor a cero'),
})

export type BidFormValues = z.infer<typeof bidSchema>
