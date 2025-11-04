# Implementation Plan

- [x] 1. Refactor `determineProductAvailability()` function





  - [x] 1.1 Add capability flags to return structure




    - Add `has_preorder` and `has_sameday` boolean flags to the result array
    - Keep existing return keys: `is_unavailable`, `unavailable_reason`, `should_display`
    - Update function documentation to describe new return structure

    - _Requirements: 2.1, 2.2, 2.3, 4.2_
  
  - [x] 1.2 Simplify capability determination logic





    - Extract pre-order capability check: `in_array($status_id, [1, 2, 3]) && $preorder_stock > 0`
    - Extract same-day capability check for status 4 products
    - Extract same-day capability check for dual-capability products (status 1/2/3 with availtoday_status_id)

    - Remove nested conditionals and use linear flow (STEP 1 → STEP 2 → STEP 3)
    - _Requirements: 2.1, 2.2, 2.4, 7.1, 7.2, 7.3_
  
  - [x] 1.3 Update unavailability determination




    - Set `is_unavailable = !has_preorder && !has_sameday`

    - Simplify unavailable reason logic based on product type
    - Maintain existing visibility rules logic (no changes)
    - _Requirements: 3.4, 4.2_
  
  - [x] 1.4 Add inline comments for clarity





    - Add STEP 1, STEP 2, STEP 3 comments to mark major sections
    - Document each capability check with inline comments
    - Explain the logic flow for future maintainers
    - _Requirements: 2.4, 4.3_

- [x] 2. Verify existing functionality still works






  - [x]* 2.1 Test that badge display logic works with new capability flags

    - Verify existing badge code can access `has_preorder` and `has_sameday` flags
    - Verify badges display correctly for all product types
    - _Requirements: 1.1, 1.2, 1.3_
  

  - [ ]* 2.2 Test product sorting with refactored function
    - Verify sort order remains: Same Day → Featured → Pre-Order → Unavailable
    - Verify dual-capability products sort correctly
    - _Requirements: 5.1, 5.2, 5.3_
  
  - [x]* 2.3 Test edge cases

    - Test products with null/missing values
    - Test products with no dates configured
    - Test products with past dates only
    - _Requirements: 7.4_
