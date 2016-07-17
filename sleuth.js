$(function()
{
	var digimon = getDigimonList();
	var skills = getSkillList();
	
	var srcHeader = "The Digimon You Have";
	var destHeader = "The Digimon You Deserve";
	var skillHeader = "Skill (Optional)";
	
	$("#src_digimon").autocomplete(
	{
		source: digimon,
		minLength: 1,
		focus: function(event, ui)
		{
			$("#src_digimon").val(ui.item.label);
			return false;
		},
		select: function(event, ui)
		{
			$("#src_digimon").val(ui.item.label);
			$("#src_box").accordion("option", "active", false);
			$("#src_id").val(ui.item.value);
			$("#src_box").children('h3').html(ui.item.label);
			return false;
		}
	});
	
	$("#dest_digimon").autocomplete(
	{
		source: digimon,
		minLength: 1,
		focus: function(event, ui)
		{
			$("#dest_digimon").val(ui.item.label);
			return false;
		},
		select: function(event, ui)
		{
			$("#dest_digimon").val(ui.item.label);
			$("#dest_box").accordion("option", "active", false);
			$("#dest_id").val(ui.item.value);
			$("#dest_box").children('h3').html(ui.item.label);
			return false;
		}
	});
	
	$("#sk1").autocomplete(
	{
		source: skills,
		minLength: 1,
		focus: function(event, ui)
		{
			$("#sk1").val(ui.item.label);
			return false;
		},
		select: function(event, ui)
		{
			var skid = '#' + $(this).attr('id');
			$(skid).val(ui.item.label);
			$(skid+'_box').accordion("option", "active", false);
			$(skid+'_id').val(ui.item.value);
			$(skid+'_box').children('h3').html(ui.item.label);
			return false;
		}
	});
	
	$("#mainform").submit(function(event)
	{
		event.preventDefault();
		$('#startpanel').fadeOut();
		$.post("brain.php", $("#mainform").serialize(), function (data)
		{
			$("#results").html(data);
			$('#results').fadeIn();
		});
	});
	
	$(".expandBox").each( function ()
	{
		$(this).accordion(
		{
			collapsible: true,
			active: (($(this).attr('id') == 'src_box' || $(this).attr('id') == 'dest_box') ? 0 : false),
			beforeActivate: function (event, ui)
			{
				if ($(this).accordion('option', 'active') == false)
				{
					var id = $(this).attr('id');
					var text = skillHeader;
					if (id == 'src_box') text = srcHeader;
					if (id == 'dest_box') text = destHeader;
					ui.newHeader.html(text);
					
					var sep = id.indexOf('_');
					var prefix = id.substring(0, sep);
					$('#'+prefix+'_id').val(-1);
				}
			}
		});
	});
	
	$('#mainform').trigger('reset');
	$('input[type="hidden"]').val(-1);
	$('#submit').button();
	$('#about').dialog( { autoOpen: false } );
	$('#wanya').click(function()
	{
		$('#about').dialog('open');
	});
});
