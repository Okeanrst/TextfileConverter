$(document).ready(function() {               
    //$("form").submit(processingForm);

    $( document ).ajaxStart(function() {
        console.log('ajaxStart');        
    });

    $( document ).ajaxStop(function() {
        console.log('ajaxStop');
    });

    function processingForm(event) {
        event.stopPropagation();
        event.preventDefault();
        var submit = $('input[name="send"]')[0];
        
        $('#message').text('');

        var fileUrl = $('input[type="file"]').val();
        
        var parts, ext;
        parts, ext = ( parts = fileUrl.split("/").pop().split(".") ).length > 1 ? parts.pop() : "";
        
        if(ext.toLowerCase() == $('select option:selected').val().toLowerCase()) {
        	$('#message').html('<div class="error">Extantion of the incoming file matches the required format.</div>');
        	return false;
        }
        submit.disabled = true;
        
        var formData = new FormData($('form')[0]);

        $.ajax({
            type: "POST",
            processData: false,
            contentType: false,
            url: 'handler.php', 
            data: formData,
            success: function(result){
                try {
                    var data = JSON.parse(result);                    
                } catch (e) {                
                    $('#message').text('File conversion error. Invalid JSON');
                    submit.disabled = false;
                    return;
                }
                $('#message').html(data);
                submit.disabled = false;
                return false;
            },
            error: function(result){
                $('#message').text('File conversion error. AJAX error');
                submit.disabled = false;
                return;                
            }
    	});
    }       
});