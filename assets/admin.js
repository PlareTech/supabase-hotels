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
});
