<?php

namespace Tutu\Withdrawal;

use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class WithdrawalController
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index(Content $content)
    {
        $content->body('<style>.krajee-default.file-preview-frame .kv-file-content{width:auto;height:auto;}</style>');
        return $content
            ->header('Withdrawal')
            ->description('&nbsp;')
            ->body($this->form());
    }

    /**
     * Edit interface.
     *
     * @param int $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Withdrawal')
            ->description('edit')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Withdrawal')
            ->description('create')
            ->body($this->form());
    }

    public function show($id, Content $content)
    {
        return $content
            ->header('Withdrawal')
            ->description('detail')
            ->body(Admin::show(WithdrawalModel::findOrFail($id), function (Show $show) {
                $show->id();
                $show->name();
                $show->value();
                $show->description();
                $show->created_at();
                $show->updated_at();
            }));
    }

    public function grid()
    {
        $grid = new Grid(new ConfigModel());

        $grid->id('ID')->sortable();
        $grid->name()->display(function ($name) {
            return "<a tabindex=\"0\" class=\"btn btn-xs btn-twitter\" role=\"button\" data-toggle=\"popover\" data-html=true title=\"Usage\" data-content=\"<code>config('$name');</code>\">$name</a>";
        });
        $grid->value();
        $grid->description();

        $grid->created_at();
        $grid->updated_at();

        $grid->filter(function ($filter) {
            $filter->disableIdFilter();
            $filter->like('name');
            $filter->like('value');
        });

        return $grid;
    }

    public function form()
    {
        $form = new Form(new WithdrawalModel());
        $data = config('admin.withdrawal') ?? [];
        foreach ($data as $key => $value) {
            $item_value = WithdrawalModel::get_config_by_key($key);
            if ($value['type'] == "text") {
                if (isset($value['help'])) {
                    $form->text($key, $value['name'])->help($value['help'])->default($item_value);
                } else {
                    $form->text($key, $value['name'])->default($item_value);
                }
            } else if ($value['type'] == "textarea") {
                $form->textarea($key, $value['name'])->rows(5)->default($item_value);
            } else if ($value['type'] == "image") {
                if ($item_value) {
                    $image_src = Storage::disk('admin')->url($item_value);
                    $js = <<<SCRIPT
                    $(function(){
                        $("[name={$key}]").parent().parent().parent().parent().children(".file-preview").show()
                        $("[name={$key}]").parent().parent().parent().parent().children(".file-preview").children(".file-drop-disabled").children(".file-preview-thumbnails").html(
                        '<div class="file-preview-frame krajee-default  file-preview-initial file-sortable kv-preview-thumb"  data-fileindex="init_0" data-template="image">'+
                            '<div class="kv-file-content">'+
                                '<img src="{$image_src}" class="kv-preview-data file-preview-image" title="" alt="" style="width:auto;height:160px;">'+
                            '<\/div>'+
                        '<\/div>')
                    })
SCRIPT;
                    Admin::script($js);
                }
                $form->image($key, $value['name']);
            }
        }
        $form->setAction('/'.config('admin.route.prefix').'/withdrawal');
        $form->footer(function ($footer) {
            // 去掉`重置`按钮
            $footer->disableReset();
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();
        });
        return $form;
    }

    public function store(Request $request)
    {
        $new_data = [];
        $data = config('admin.withdrawal') ?? [];
        foreach ($data as $key => $item) {
            if (in_array($item['type'], ["text", "textarea"])) {
                $new_data[$key] = ['value' => $request->$key, 'desc' => $item['name']];
            } else if ($item['type'] == "image") {
                if ($request->file($key)) {
                    $image_src = Storage::disk('admin')->putFile('withdrawal', $request->file($key));
                    $new_data[$key] = ['value' => $image_src, 'desc' => $item['name']];
                }
            }
        }
        DB::beginTransaction();
        $withdrawal = new WithdrawalModel();
        foreach ($new_data as $key => $item) {
            if (!$withdrawal->set_value_by_key($key, $item['value'], $item['desc'])) {
                DB::rollback();
                admin_error('编辑失败', $item['desc'] . '编辑失败');
                return redirect()->back();
            }
        }
        DB::commit();
        //修改网站名称
        $this->modifyEnv(['APP_NAME' => $new_data['web_name']['value']]);
        admin_toastr('编辑成功', 'success', ['timeOut' => 3000]);
        return Redirect::route('withdrawal.index');
    }

    public function modifyEnv(array $data)
    {
        $envPath = base_path() . DIRECTORY_SEPARATOR . '.env';
        $contentArray = collect(file($envPath, FILE_IGNORE_NEW_LINES));
        $contentArray->transform(function ($item) use ($data) {
            foreach ($data as $key => $value) {
                if (str_contains($item, $key)) {
                    return $key . '="' . $value.'"';
                }
            }
            return $item;
        });
        $content = implode($contentArray->toArray(), "\n");
        \File::put($envPath, $content);
    }
}
