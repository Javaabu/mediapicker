/** Runs once the document is parsed. */
export function onReady(callback: () => void): void {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", callback, { once: true })
    return
  }
  callback()
}

/** Runs the callback after elements are added to the page (modals, Livewire, Turbo, …). */
export function watch(callback: () => void): MutationObserver {
  let scheduled = false
  const observer = new MutationObserver(() => {
    if (scheduled) return
    scheduled = true
    queueMicrotask(() => {
      scheduled = false
      callback()
    })
  })
  observer.observe(document.body, { childList: true, subtree: true })
  return observer
}
