(function(window, document, $, undefined){

	window.WPCB = {};
	WPCB.syncConfession=function(){
		var ca_inner=$('#confessions_area_inner');
		var odd_confessions=ca_inner.find('#odd_confessions');
		var even_confessions=ca_inner.find('#even_confessions');
		var last_con_id=0;

		setInterval(function(){ 
		console.log('WPCB syncronize confessions');
		if((odd_confessions.children().length > 0) || (even_confessions.children().length > 0 )){
			last_odd_con_id=odd_confessions.children().first().attr('id');
			last_even_con_id=even_confessions.children().first().attr('id');

			if(last_even_con_id==undefined){
				last_even_con_id=0;
			}
			if(last_odd_con_id==undefined){
				last_odd_con_id=0;
			}
			
			if(last_odd_con_id > last_even_con_id){
				last_con_id=last_odd_con_id;
			}

			if(last_odd_con_id < last_even_con_id){
				last_con_id=last_even_con_id;
			}
		}

	
		var params={
			'action' : 'wpcb_sync_confessions',
			'last_con_id' : last_con_id

		};
		$.post(wpcb_ajax.ajaxurl,params,function(res){
					var obj=JSON.parse(res);
					var oddDone=0;
					var evenDone=0;
					if(obj.odd){
						if(odd_confessions.children().length>0){
							odd_confessions.children().first().before(obj.odd);
						}else{
							odd_confessions.html(obj.odd);
						}
						oddDone=1;
					}
					if(obj.even){
						if(even_confessions.children().length>0){
							even_confessions.children().first().before(obj.even);
						}else{
							even_confessions.html(obj.even);
						}
						
						evenDone=1;
					}

					if(obj.new && (evenDone || oddDone)){
					$('.new_feeds').show();
					}

					

				});		
			}, 10000);

	},
	WPCB.firstLoadConfession=function(){
		console.log('first load confession');

		var ca_inner=$('#confessions_area_inner');
		var odd_confessions=ca_inner.find('#odd_confessions');
		var even_confessions=ca_inner.find('#even_confessions');

		$.post(wpcb_ajax.ajaxurl,{'action':'fetch_old_confession'},function(res){
					var obj=JSON.parse(res);
					
					if(obj.odd){
						odd_confessions.html(obj.odd);
					}
					if(obj.even){
						even_confessions.html(obj.even);
					}

				});	
	},
	WPCB.manageActions = function(){

	var cf_area=$('#display_confessions_area');
	var cf_area_inner=$('#confessions_area_inner');
	var action = '';
	cf_area_inner.on('click','.dashicons',function(){

		//var confirm= confirm('');
		var allow;

		var click = $(this);
		var parent = $(this).parent().parent();
		var confession_id = parent.attr('id');
		if(click.hasClass('dashicons-trash')){
			action = 'delete';
			allow = confirm("Are you sure to delete this confession ?");
		}

		if(click.hasClass('dashicons-hidden')){
			action = 'block';

			if(click.hasClass('blocked')){
				allow = confirm("Are you sure to unblock this confession ?");
			}else{
				allow = confirm("Are you sure to block this confession ?");
			}
			
		}
		if(allow==false || action==''){
			return false;
		}
		var params={
				'action' : 'manage_confession_actions',
				'apply' : action,
				'confession_id' : confession_id
			};

			//console.log(params);
			$.post(wpcb_ajax.ajaxurl,params,function(res){
					var obj=JSON.parse(res);

					if(obj.success){
						alert(obj.success);
						if(obj.action=='blocked'){
							click.addClass('blocked');
						}
						if(obj.action=='unblocked'){
							click.removeClass('blocked');
						}

						if(obj.action=='deleted'){
							parent.remove();
						}

					}else{
						alert(obj.error);
					}
			});
		
	});

	},
	WPCB.manageLike = function(){

		var cf_area=$('#display_confessions_area');
		var cf_area_inner=$('#confessions_area_inner');
		var like_box=cf_area_inner.find('p span.like-cf');
		var action;

		cf_area_inner.on('click','.like-cf',function(){
			var hit=$(this);
			var parent=hit.parent();
			var con_id=parent.attr('id');
			var like_counts=parent.find('.like_counts');
			var dislike_counts=parent.find('.dislike_counts');
			var confession_msg=parent.find('.conf_msg');

			if($(this).hasClass('dashicons-thumbs-up')){
				action='like';
			}

			if($(this).hasClass('dashicons-thumbs-down')){
				action='dislike';
			}

			var params={
				'action' : 'manage_confession_likes',
				'apply' : action,
				'confession_id' : con_id
			};

			//console.log(params);
			$.post(wpcb_ajax.ajaxurl,params,function(res){
					var obj=JSON.parse(res);

					if(obj.success!=undefined){

					
						if(obj.success.current_user_action=='liked'){
						hit.addClass('liked');
						hit.siblings().removeClass('disliked');
						}else if(obj.success.current_user_action=='disliked'){
						hit.addClass('disliked');
						hit.siblings().removeClass('liked');
						}
					
						
					if(obj.success.likes!=undefined){
						like_counts.html('('+obj.success.likes+')');
					}

					if(obj.success.dislikes!=undefined){
						dislike_counts.html('('+obj.success.dislikes+')');
					}
					}else{
						confession_msg.show().css('color','red').html(obj.error);
					}
					
					setTimeout(function(){ 
						confession_msg.hide().css('color','').html('');
						// hit.removeClass('liked disliked');
					}, 1000);
					});
		});

		
	},
	WPCB.init = function() {
		// put your custom functions here
		console.log('WPCB loaded');
		var cf_area=$('#display_confessions_area');
		var cf_area_inner=$('#confessions_area_inner');
		var cf=$('#confession_form');
		var cform=cf.find('form');
		var ctitle=cform.find('#wpcb_title');
		var cdesc=cform.find('#wpcb_desc');
		var cauthor=cform.find('#wpcb_author_name');
		var ccategory=cform.find('#wpcb_category');
		var cnonce = cform.find('#verify_cf_submission');
		var message=$('.wpcb_messages');
		var odd_confessions=cf_area_inner.find('#odd_confessions');
		var even_confessions=cf_area_inner.find('#even_confessions');
		var main = $('#main');

		WPCB.firstLoadConfession();
		WPCB.syncConfession();
		WPCB.manageLike();
		WPCB.manageActions();
		
		cf_area.on('click','#view_cform',function(){
			cf.toggle();
			if($(this).hasClass('hidden_cform')){
				cf.show();
				$(this).removeClass('hidden_cform');
				$(this).text('Hide Confession Form');
			}else{
				cf.hide();
				$(this).addClass('hidden_cform');
				$(this).text('View Confession Form');
			}
		});
		
		cf.on('click','#wpcb_add_confession',function(){
			var params=WPCB.validateForm(ctitle,cdesc,cauthor,ccategory,cnonce);

			if(params!=false){
			console.log(params);	
			$.post(wpcb_ajax.ajaxurl,params,function(res){
					var obj=JSON.parse(res);
					
					if(obj.success){
						message.show().html(obj.success);
					}else{
						message.show().html(obj.error);
					}

					ctitle.val('');
					cdesc.val('');
					cauthor.val('')
					ccategory.val(1);
					main.scrollTop(0);
					setTimeout(function(){ 

				console.log('WPCB Saved');
				message.hide().html('');
					
				}, 10000);
			}); //Ajax request


			}
			
		});

		cf_area.on("click",'.new_feeds',function(){
			odd_confessions.children().removeClass('hide_new');
			even_confessions.children().removeClass('hide_new');
			$(this).hide();
			cf_area_inner.scrollTop(0);
		});

		cf_area.on("click",'.like_counts',function(){
			$(this).prev().toggleClass('hide');
		});
		
		cf_area.on("click",'.dislike_counts',function(){
			$(this).prev().toggleClass('hide');
		});
		
	},

	WPCB.validateForm=function(ctitle,cdesc,cauthor,ccategory,cnonce){
		var error=0;

		if(ctitle.val().length<3){
			ctitle.addClass('show-danger');
			error=1;
		}else{
			ctitle.removeClass('show-danger').addClass('show-success');
		}
		if(cdesc.val().length<200){
			cdesc.addClass('show-danger');
			error=1;
		}else{
			cdesc.removeClass('show-danger').addClass('show-success');
		}
		if(cauthor.val().length==0){
			cauthor.addClass('show-danger');
			error=1;
		}else{
			cauthor.removeClass('show-danger').addClass('show-success');
		}
		
		if(error){
			console.log('Validation Error');
			return false;
			
		}else{

			var cf_params={
				'action' : 'cf_save_confession',
				'cf_title' : ctitle.val(),
				'cf_category' : ccategory.val(),
				'cf_author' : cauthor.val(),
				'cf_desc' : cdesc.val(),
				'verify_cf_submission' : cnonce.val()
			}
			return cf_params;
		}
		//console.log(ctitle.length);
	}

	$(document).on( 'ready load_ajax_content_done', WPCB.init );

})(window, document, jQuery);