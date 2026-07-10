/** Formatos de video permitidos (espejo de App\Enums\VideoFormat en el backend). */
export const VIDEO_FORMATOS = ['9:16', '1:1', '16:9'] as const

export type VideoFormato = (typeof VIDEO_FORMATOS)[number]

export const VIDEO_FORMATO_OPTIONS: { label: string; value: VideoFormato }[] = [
  { label: 'Vertical (9:16)', value: '9:16' },
  { label: 'Cuadrado (1:1)', value: '1:1' },
  { label: 'Horizontal (16:9)', value: '16:9' },
]
