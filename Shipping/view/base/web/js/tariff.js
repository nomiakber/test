require(["jquery"], function($){
    $(document).ready(function() {
        
		$(document).on("click",".btn-submit_tariff",function(){
			var ths = $(this);
			var form = ths.closest('form');
			var error = false;
			
			var errorObj = form.find('span.error');
			var htmlObj = form.find('div.response');
			errorObj.hide(); htmlObj.hide();
			
			form.find('.required-entry').css({'border-color':'#ccc'});
			form.find('.required-entry').each(function(){
				var fld = $(this); var fldval = fld.val().trim();
				if( fldval == '' ){
					fld.css({'border-color':'#d00'}).hide().fadeIn();
					error = 'All fields are requied !';
				} else {
					if( fld.hasClass('check-number') ){
						fldval = fldval*1;
						if( isNaN(fldval) ){
							fld.css({'border-color':'#d00'}).hide().fadeIn();
							error = fld.parent().parent().find('label').text().replace(':','')+' field is invalid';
						}
					}
				}
			});
			
			if( error ){ 
				errorObj.html(error).hide().fadeIn();
			} else {
				ths.html('Processing ...').prop('disabled',true);
				var actionUrl = form.attr('action')+'?'+form.serialize();
				$.ajax({
					url:actionUrl, 
					method: 'GET',
					success:function(res){
						ths.html('Submit').prop('disabled',false);
						if( res.result ){
							rs = res.result;
							var chrWieght = rs.weightcharges*1,
							chrGst = rs.gstcharges*1,
							chrOther = rs.othercharges*1;
							htmlObj.html('Weight Charges &nbsp; : &nbsp; <b>'+chrWieght.toFixed(2) +'</b><br>GST Charges &nbsp; &nbsp; &nbsp; &nbsp; : &nbsp; <b>'+chrGst.toFixed(2) +'</b><br>Other Charges &nbsp; &nbsp; : &nbsp; &nbsp;<b>'+chrOther.toFixed(2)+'</b>').fadeIn();
						}
						if( res.error ){
							errorObj.html('Error:' +res.error).fadeIn();
						}
					}
				});
			}
		});	
			
    });
});