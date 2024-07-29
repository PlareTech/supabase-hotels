jQuery(document).ready(function($) {
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
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('Error fetching hotels:', textStatus, errorThrown);
                $('#hotel-list').html('<p>Error fetching hotels. Check console for details.</p>');
            }
        });
    }

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
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error importing hotel:', textStatus, errorThrown);
                    alert('Error importing hotel. Check console for details.');
                }
            });
        }
    });

    fetchHotels();
});
