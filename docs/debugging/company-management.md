# Company Management Page Refactor

**Date:** {{ date('Y-m-d H:i:s') }}

## Summary

-   If the logged-in user is a company admin, the page now displays a detailed card of their company (logo, name, email, domain, status, address, phone) using a layout inspired by Metronic8 demo60 account/settings.
-   If the user is not a company admin (e.g., SuperAdmin), the page displays the company datatable as before.

## Technical Notes

-   Logic is handled in the Blade view using `CompanyUser::isCompanyAdmin()` and `CompanyUser::getUserMapping()`.
-   The controller passes `$company` to the view for company admin users.
-   The datatable and Livewire form remain available for SuperAdmin and other roles.
-   This improves UX for company admins by focusing on their own company details.

## Changelog

-   [{{ date('Y-m-d H:i:s') }}] Refactor: Conditional company card/datatable display, Metronic8-inspired UI, documentation added.
