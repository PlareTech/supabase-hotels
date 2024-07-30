jQuery(document).ready(function($) {
    $('#hotel-list').on('click', '.import-hotel', function(e) {
        e.preventDefault();

        var hotelId = $(this).data('hotel-id');
        var link = prompt('Enter the link:');

        if (link) {
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'import_hotel',
                    hotel_id: hotelId,
                    link: link,
                    nonce: importHotelData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Hotel imported successfully');
                    } else {
                        alert('Failed to import hotel');
                    }
                }
            });
        }
    });

    function fetchHotels() {
        $.ajax({
            url: ajaxurl,
            method: 'GET',
            data: {
                action: 'fetch_hotels',
                nonce: importHotelData.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#hotel-list').html(response.data);
                } else {
                    $('#hotel-list').html('<p>No hotels found</p>');
                }
            }
        });
    }

    fetchHotels();

    // Handle click event for Copy or Insert Link
    $('#hotel-list').on('click', '.copy-or-insert-link', function(e) {
        e.preventDefault();
        var longLink = $(this).data('long-link');

        if (longLink) {
            // Copy the long link to the clipboard
            var tempInput = document.createElement('input');
            tempInput.value = longLink;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            alert('Link copied to clipboard');
        } else {
            // Prompt user to insert a link
            var newLink = prompt('Please enter the link:');
            if (newLink) {
                // Update the Supabase with the new link (you'll need to handle this in your backend)
                var hotelId = $(this).closest('.notice').find('.import-hotel').data('hotel-id');
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'update_long_link',
                        hotel_id: hotelId,
                        long_link: newLink,
                        nonce: importHotelData.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Link updated successfully');
                            location.reload(); // Refresh to show the updated link
                        } else {
                            alert('Failed to update link');
                        }
                    }
                });
            }
        }
    });
});
