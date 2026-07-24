import { z } from 'zod'

export const wineDetailsSchema = z.object({
  year: z.coerce
    .number({ message: 'El año es requerido' })
    .int('El año debe ser un número entero')
    .min(1900, 'El año debe ser mayor a 1900')
    .max(2100, 'El año no puede superar 2100'),
  winery_guid: z.string().uuid('Seleccioná una bodega'),
  grape_variety_guid: z.string().uuid('Seleccioná una variedad de uva'),
  wine_region_guid: z.string().uuid('Seleccioná una región vitivinícola'),
})

/** Bound to vee-validate — covers the generic + wine-detail fields. */
export const productDetailsSchema = z.object({
  title: z.string().min(1, 'El título es requerido').max(255, 'Máximo 255 caracteres'),
  description: z.string().max(5000).optional().nullable(),
  status: z.enum(['draft', 'published']).optional(),
  wine_details: wineDetailsSchema,
})

const imageEntrySchema = z
  .object({
    image_id: z.number().optional(),
    token: z.string().optional(),
    is_main: z.boolean(),
  })
  .refine((image) => Boolean(image.image_id) !== Boolean(image.token), {
    message: 'La imagen debe tener exactamente un identificador o un token',
  })

/** Validated manually (not via vee-validate) because the gallery is managed
 * as free-form component state, not a plain form field. */
export const productImagesSchema = z
  .array(imageEntrySchema)
  .min(1, 'Cargá al menos una imagen')
  .refine((images) => images.filter((image) => image.is_main).length === 1, {
    message: 'Debe haber exactamente una imagen principal',
  })

export type ProductDetailsFormValues = z.infer<typeof productDetailsSchema>
export type ProductImageEntry = z.infer<typeof imageEntrySchema>
