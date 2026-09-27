<?php
/**
 * MIGX input renderer for which_editor = mxEditorJs.
 *
 * MIGX maps inputTVtype "richtext" to "migx" + strtolower(which_editor).
 *
 * @package mxeditorjs
 */

use MODX\Revolution\modTemplateVarInputRender;

class migxmxeditorjsInputRender extends modTemplateVarInputRender
{
    public function getTemplate()
    {
        $corePath = $this->modx->getOption(
            'mxeditorjs.core_path',
            null,
            $this->modx->getOption('core_path') . 'components/mxeditorjs/'
        );

        return $corePath . 'elements/tv/input/tpl/migxmxeditorjs.tpl';
    }
}

return 'migxmxeditorjsInputRender';
