# Company Settings UI

## Changelog

-   2024-06-09: UI now hides entire section (Purchasing, Mutation, Usage, Notification, Reporting) if all sub-features are disabled in config. Added `isSectionEnabled` helper in Livewire for this purpose.

## Section Visibility Logic

-   Each main section (Purchasing, Mutation, Usage, Notification, Reporting) is only shown if at least one sub-feature is enabled.
-   The helper `$this->isSectionEnabled($sectionSettings)` is used in the Blade to determine visibility.
-   This ensures a clean UI and prevents empty cards/sections from being rendered.

## Example Usage in Blade

```blade
@if($this->isSectionEnabled($purchasingSettings))
    <!-- Purchasing card -->
@endif
```

## Helper Implementation

```
public function isSectionEnabled($sectionSettings)
{
    if (!is_array($sectionSettings)) return false;
    foreach ($sectionSettings as $key => $sub) {
        if (is_array($sub) && array_key_exists('enabled', $sub)) {
            if ($sub['enabled']) return true;
        } elseif (is_array($sub)) {
            if ($this->isSectionEnabled($sub)) return true;
        } elseif ($sub === true) {
            return true;
        }
    }
    return false;
}
```

## Impact

-   UI is now more robust and user-friendly, hiding irrelevant/disabled sections automatically.
-   Future-proof: as new features are added, this logic will keep the UI clean.
