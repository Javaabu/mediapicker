import { Alert } from "@javaabu/forms"
import { usePage } from "@inertiajs/react"

interface FlashProps {
  flash?: { success?: string | null; error?: string | null }
}

/** The page's flashed success or error message. */
export function Flash() {
  const flash = (usePage().props as FlashProps).flash

  return (
    <>
      {flash?.success ? <Alert type="success">{flash.success}</Alert> : null}
      {flash?.error ? <Alert type="danger">{flash.error}</Alert> : null}
    </>
  )
}
