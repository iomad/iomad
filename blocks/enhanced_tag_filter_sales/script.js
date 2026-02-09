// Initialize an array to track selected tags
var selectedTags = [];
var selectedTagIds = [];
var xhr_r = new XMLHttpRequest();

// Attach a click event listener to each tag with the class 'standardtag'
document.querySelectorAll('.standardtags').forEach(function(tagElement) {
    tagElement.addEventListener('click', function(event) {
        var tag = event.currentTarget.dataset.tag;
        var tagid = event.currentTarget.dataset.id;
        var isSelected = event.currentTarget.classList.contains('selected');

        if (isSelected) {
            // Deselect tag
            selectedTags = selectedTags.filter(function(item) {
                return item !== tagid;
            });
            selectedTagIds = selectedTagIds.filter(function(ele) {
                return ele !== tagid;
            });
            event.currentTarget.classList.remove('selected');
        } else {
            // Select tag
            selectedTags.push(tagid);
            selectedTagIds.push(tagid);
            event.currentTarget.classList.add('selected');
        }

	// Fetch available tags and update their status
	fetchAvailableTags(selectedTags);
	// Update filter and display results
	xhr_r.abort();
	updateFilterResults(selectedTags);
	
    });
});

// Call the updateCountdown function every second



// Attach a click event listener to the reset filter button
document.getElementById('reset-filter').addEventListener('click', function() {
    // Deselect all tags
    document.querySelectorAll('.standardtags.selected').forEach(function(tagElement) {
        tagElement.classList.remove('selected');
    });

    // Clear the selectedTags array
    selectedTags = [];

    // Fetch available tags and update their status
    fetchAvailableTags([]);

    // Update filter and display results
    updateFilterResults(selectedTags);

     // Activate all tags
    document.querySelectorAll('.standardtags').forEach(function(tagElement) {
        tagElement.classList.remove('deactivated');
        tagElement.classList.add('activated');
        tagElement.style.pointerEvents = 'auto';
        tagElement.style.cursor = 'pointer';
        tagElement.style.color = '';
    });
});

// Function to fetch available tags based on selected tags
var fetchAvailableTags = function(tags) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '../../../../blocks/enhanced_tag_filter/available_tags.php?tags=' + encodeURIComponent(JSON.stringify(tags)), true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                var availableTags = JSON.parse(xhr.responseText);
                // Determine if we should activate all tags based on response or if no tags are selected
                var activateAll = availableTags.length === 0 || tags.length === 0;
                document.querySelectorAll('.standardtags').forEach(function(tagElement) {
                    var tagLink = document.querySelector(`a[data-id="${tagElement.dataset.id}"]`);
                    if (tagLink) {
                        if (activateAll) {
                            // Activate all tags when no specific tags are selected
                            tagLink.classList.remove('deactivated');
                            tagLink.classList.add('activated');
                            tagLink.style.pointerEvents = 'auto';
                            tagLink.style.cursor = 'pointer';
                            tagLink.style.color = '';  // Reset any styling if needed
                        } else {
                            var tagId = tagElement.dataset.id;
                            if (availableTags[0].includes(tagId)) {
                                // Activate only specific tags based on the server response
                                tagLink.classList.remove('deactivated');
                                tagLink.classList.add('activated');
                                tagLink.style.pointerEvents = 'auto';
                                tagLink.style.cursor = 'pointer';
                                tagLink.style.color = '';
                            } else {
                                // Deactivate other tags
                                tagLink.classList.add('deactivated');
                                tagLink.classList.remove('activated');
                                tagLink.style.pointerEvents = 'none';
                                tagLink.style.cursor = 'not-allowed';
                                //tagLink.style.color = 'grey';
                            }
                        }
                    }
                });
            } else {
                console.error('Failed to fetch tags:', xhr.statusText);
            }
        }
    };
    xhr.send();
};

// Trigger initial activation of all tags on document load
document.addEventListener('DOMContentLoaded', function() {
    fetchAvailableTags([]); // This will activate all tags initially
});



// Function to update filter results based on selected tags
var updateFilterResults = function(tags) {    
    xhr_r.open('GET', '../../../../blocks/enhanced_tag_filter/filter_results.php?tags=' + encodeURIComponent(JSON.stringify(tags)), true);
    xhr_r.onreadystatechange = function() {
        if (xhr_r.readyState === 4) {
            if (xhr_r.status === 200) {
                document.getElementById('filtered-results').innerHTML = xhr_r.responseText;
            } else {
                // console.error('Error fetching filtered results:', xhr.statusText);
                // Display error message to users
                // document.getElementById('filtered-results').innerHTML = '<p>Error fetching filtered results. Please try again later.</p>';
            }
        }
    };
    xhr_r.send();
};
