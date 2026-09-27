{$tv->get('value')|escape}

{literal}
Ext.onReady(function() {
{/literal}
    var tvid = 'tv{$tv->id}';
    var field = Ext.get(tvid);
    if (!field) {
        return;
    }
    MODx.makeDroppable(field);
{literal}
    field.onLoad = function() {
        if (window.MODx && typeof MODx.loadRTE === 'function') {
            MODx.loadRTE(tvid);
        }
    };
    field.onHide = function() {
        if (window.MODx && typeof MODx.unloadRTE === 'function') {
            MODx.unloadRTE(tvid);
        }
    };
    field.onBeforeSubmit = function() {
        if (typeof window.MxEditorJsFlush === 'function') {
            window.MxEditorJsFlush(tvid);
        }
    };
});
{/literal}
