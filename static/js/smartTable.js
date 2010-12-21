;(function($) {$.extend($.fn, {smartTable: function(overload) {
var settings = {
	"oLanguage": {
		"sProcessing":   _("Processing..."),
		"sLengthMenu":   _("Show _MENU_ entries"),
		"sZeroRecords":  _("No matching records found"),
		"sInfo":         _("Showing _START_ to _END_ of _TOTAL_ entries"),
		"sInfoEmpty":    _("Showing 0 to 0 of 0 entries"),
		"sInfoFiltered": _("(filtered from _MAX_ total entries)"),
		"sInfoPostFix":  "",
		"sSearch":       _("Search:"),
		"sUrl":          _(""),
		"oPaginate": {
			"sFirst":    _("First"),
			"sPrevious": _("Previous"),
			"sNext":     _("Next"),
			"sLast":     _("Last")
		}
	},
	"aoColumns": function(table) {
		var columns = [];
		$('thead > tr > th', $(table)).each(function(i, el) {
			columns.push($(el).hasClass('disabledSorting') ? {bSortable: false} : null);
		});
		return columns;
	}(this),
	"bProcessing": true,
	"bServerSide": true,
	"sAjaxSource": $(this).data('source'),
	"fnServerData": function ( sSource, aoData, fnCallback ) {
		setTimeout(function() {$table.sSource = sSource;}, 0);
		$.queryController(sSource, fnCallback, aoData);
	},
	"fnDrawCallback": function() {
		setTimeout(function () {
		alert($table.data('editurl'));
			$($table.fnGetNodes()).each(function(i, el) {
				var id = $('td:last', el).text();
				$(el).attr('id', id);
				$('td:last', el)
				.html('')
				.append($table.data('editurl') != null ? '<a href="/' + $('html').attr('lang') + $table.data('editurl') + '/' + id + '">' + _('Edit') + '</a>' : '')
				.append(
					$('<a href="#">').text(_('Delete'))
					.data('id', id).click(function() {
						if (confirm(_("Are you sure?"))) {
							$.queryController($table.sSource + 'Delete', function(result) {
								if (!result.success) {
									if (result.goLoginPage != null) {
											location.href = '/' + $('html').attr('lang') + '/account/login?backurl=' + $.urlencode(location.pathname);
									}
									else {
										alert(_(result.error));
									}
								}
								else {
									$table.fnDeleteRow();
								}									
							}, {id: $(this).data('id')});
						}
						return false;
					})
				);
			});
			$('td:not(:last)', $table.fnGetNodes()).editable( '/component/' + $table.sSource + '/json', {
				"callback": function( result, y ) {
					result = eval('('+result+')');
					
					if (!result.success) {
						if (result.goLoginPage != null) {
							location.href = '/' + $('html').attr('lang') + '/account/login?backurl=' + $.urlencode(location.pathname);
						}
						else {
							alert(_(result.error));
						}
						return;
					}
					
					var aPos = $table.fnGetPosition( this );
					$table.fnUpdate( result.value, aPos[0], aPos[1] );
					return result.value;
				},
				"submitdata": function ( value, settings ) {
					return {
						"action": "EditColumn",
						"id": this.parentNode.getAttribute('id'),
						"column": $table.fnGetPosition( this )[2]
					};
				},
				'placeholder': $('<div>').html('<i class="i18n">Not specified</i>').i18n().html(),
				"height": "25px",
				"cssclass" : "editableInputForm",
			});		
		}, 0);
	}
};
$.extend(settings, overload);
$table = $(this);
$table.dataTable(settings);
return this;
}});
})(jQuery);
