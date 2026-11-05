import { icons } from "./icons"

type Child = Node | string | null | undefined | false
type Attributes = Record<string, string | number | boolean | null | undefined | EventListener>

/** Creates an element: attributes (`on*` functions become listeners) and children. */
export function h<K extends keyof HTMLElementTagNameMap>(
  tag: K,
  attributes: Attributes = {},
  ...children: Child[]
): HTMLElementTagNameMap[K] {
  const element = document.createElement(tag)

  for (const [name, value] of Object.entries(attributes)) {
    if (value === null || value === undefined || value === false) continue
    if (typeof value === "function") {
      element.addEventListener(name.slice(2).toLowerCase(), value)
      continue
    }
    element.setAttribute(name, value === true ? "" : String(value))
  }

  for (const child of children) {
    if (child === null || child === undefined || child === false) continue
    element.append(child)
  }

  return element
}

/** A Lucide icon as an inline SVG, hidden from assistive technology. */
export function icon(name: string, className = "mp-icon"): SVGSVGElement {
  const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg")
  svg.setAttribute("viewBox", "0 0 24 24")
  svg.setAttribute("fill", "none")
  svg.setAttribute("stroke", "currentColor")
  svg.setAttribute("stroke-width", "2")
  svg.setAttribute("stroke-linecap", "round")
  svg.setAttribute("stroke-linejoin", "round")
  svg.setAttribute("aria-hidden", "true")
  svg.setAttribute("focusable", "false")
  svg.setAttribute("class", className)
  svg.innerHTML = icons[name] ?? icons.file ?? ""
  return svg
}

/** Calls `fn` once the calls stop for `wait` ms. */
export function debounce<A extends unknown[]>(fn: (...args: A) => void, wait = 300) {
  let timer: ReturnType<typeof setTimeout> | undefined
  return (...args: A) => {
    clearTimeout(timer)
    timer = setTimeout(() => fn(...args), wait)
  }
}

/** The page's text direction. */
export function documentDirection(): "rtl" | "ltr" {
  return document.documentElement.dir === "rtl" || document.body?.dir === "rtl" ? "rtl" : "ltr"
}
