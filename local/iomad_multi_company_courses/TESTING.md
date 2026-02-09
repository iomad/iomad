# Testing the Enhanced Sorting Functionality

## Test Steps:

1. **Access the Plugin**:
   - Go to Site Administration → IOMAD → Multi-Company Courses
   - Or navigate via the IOMAD menu

2. **Search for Companies**:
   - Enter a company code pattern (e.g., "test" or "platin")
   - Click "Search companies"
   - Verify matching companies are displayed

3. **Test Sorting**:
   - In the Course Selection section, you should see a "Sort courses by" dropdown
   - Default should be "Course name (A-Z)"
   - Change to "Creation date (newest first)"
   - **The form should automatically refresh without losing the company search results**
   - Courses should now be sorted by creation date (newest first)
   - Each course should show: "Course Name (Created: Date)"

4. **Verify State Persistence**:
   - After changing sort order, the company search results should still be visible
   - The sort dropdown should maintain your selection
   - You can switch back and forth between sorting options seamlessly

## Expected Behavior:

✅ **Working**: Sort changes automatically without button clicks
✅ **Working**: Company search results are preserved when changing sort
✅ **Working**: Course creation dates are displayed
✅ **Working**: Sort preference is maintained

## If Issues Occur:

- Clear Moodle caches: Site Administration → Development → Purge all caches
- Check browser console for JavaScript errors
- Verify the enhanced_potential_company_course_selector class is being used