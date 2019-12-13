jQuery(document).ready(function($){
	$('#cc_form').submit(function(e){
		e.preventDefault();

		var form = $(this);
		var remote_response = $('.remote_response');

		$.ajax({
			type: 'POST',
			url: ajax_obj.ajaxurl, 
			data: form.serialize(), // serializes the form's elements.
			beforeSend: function(){
				remote_response.html('');
                $('.spinner_loader').fadeIn();
            },
			success: function (data) {
				setTimeout(function(){
                
					if(data.status=='success'){
						remote_response.html(data.html)
					} else {
						remote_response.html('<p>' + data.message + '</p>');
					}

				}, 1400);
			}, 
            complete: function(){
            	setTimeout(function(){
                $('.spinner_loader').fadeOut();}
                , 1000);
            }
		})

	});
});