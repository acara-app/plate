import * as React from "react"

import { cn } from "@/lib/utils"

function ButtonGroup({
  className,
  ...props
}: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="button-group"
      className={cn(
        "group/button-group inline-flex items-stretch rounded-full",
        "[&>:first-child]:rounded-l-full [&>:first-child]:rounded-r-none [&>:last-child]:rounded-r-full [&>:last-child]:rounded-l-none [&>:not(:first-child):not(:last-child)]:rounded-none",
        className,
      )}
      {...props}
    />
  )
}

export { ButtonGroup }
