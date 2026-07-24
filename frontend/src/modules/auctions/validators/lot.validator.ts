import { z } from 'zod'

/** Matches the backend's `decimal:0,2` rule — up to 2 decimal places, > 0. */
const moneyString = z
  .string()
  .min(1, 'Este campo es requerido')
  .regex(/^\d+(\.\d{1,2})?$/, 'Usá hasta 2 decimales')
  .refine((v) => Number(v) > 0, 'Debe ser mayor a cero')

const optionalMoneyString = z
  .string()
  .regex(/^\d+(\.\d{1,2})?$/, 'Usá hasta 2 decimales')
  .refine((v) => Number(v) > 0, 'Debe ser mayor a cero')
  .optional()
  .or(z.literal(''))
  .nullable()

export const lotDetailsSchema = z.object({
  lot_number: z.string().min(1, 'El número de lote es requerido').max(50, 'Máximo 50 caracteres'),
  title: z.string().max(255, 'Máximo 255 caracteres').optional().or(z.literal('')).nullable(),
  starting_price: moneyString,
  bid_increment: moneyString,
  reserve_price: optionalMoneyString,
  // Only the two non-terminal statuses are user-selectable here — sold/unsold
  // are reachable only through the close() action, never this form.
  status: z.enum(['scheduled', 'open']).optional(),
})

const lotProductEntrySchema = z.object({
  product_guid: z.string().uuid('Producto inválido'),
  quantity: z.coerce.number().int('La cantidad debe ser un número entero').min(1, 'La cantidad debe ser al menos 1'),
})

/** Validated manually (not via vee-validate) — the composer is free-form
 * component state, matching the ProductImageGalleryUploader precedent. */
export const lotProductsSchema = z
  .array(lotProductEntrySchema)
  .min(1, 'El lote debe tener al menos un producto')
  .refine(
    (items) => new Set(items.map((i) => i.product_guid)).size === items.length,
    'No se puede repetir el mismo producto en el lote',
  )

export type LotDetailsFormValues = z.infer<typeof lotDetailsSchema>
export type LotProductEntry = z.infer<typeof lotProductEntrySchema>
