/** Local UI state for one image in the gallery uploader (kept or newly staged). */
export interface GalleryItem {
  /** Stable key for v-for: the token for new images, or `image-{id}` for kept ones. */
  key: string
  url: string
  is_main: boolean
  /** Present only for a kept (already persisted) image. */
  image_id?: number
  /** Present only for a newly staged image. */
  token?: string
}
