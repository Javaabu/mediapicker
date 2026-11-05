import { icons } from "../icons"

export interface IconProps {
  /** A Lucide icon name the package ships (resources/js/icons.ts). */
  name: string
  className?: string
}

/** A Lucide icon, hidden from assistive technology. */
export function Icon({ name, className = "mp-icon" }: IconProps) {
  return (
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={2}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      focusable="false"
      className={className}
      dangerouslySetInnerHTML={{ __html: icons[name] ?? icons.file ?? "" }}
    />
  )
}
