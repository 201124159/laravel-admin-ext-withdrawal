<?php
namespace Tutu\Withdrawal\Functionlist;

use Encore\Admin\Layout\Content;
class AdminIndex
{
    public function index(Content $content)
    {
        $content->body('<style>.krajee-default.file-preview-frame .kv-file-content{width:auto;height:auto;}</style>');
        return $content
            ->header('Withdrawal')
            ->description('&nbsp;')
            ->body($this->form());
    }
}