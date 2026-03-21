<form class="form-horizontal" method="post" action="{$form_action|escape:'htmlall':'utf-8'}">
    <div class="panel">
        <div class="panel-heading">
            {l s='Payment modes' d='Modules.Agpaymentmodes.Admin'}
            <span class="panel-heading-action">
                <a id="desc-attribute_group-new" class="list-toolbar-btn" href="{$form_action|escape:'htmlall':'utf-8'}{$form_action_sep}{if isset($smarty.get._token)}_token={$smarty.get._token|escape:'url'}&{/if}action=new">
                    <span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="{l s='Add new payment mode' d='Modules.Agpaymentmodes.Admin'}" data-html="true" data-placement="top">
                        <i class="process-icon-new"></i>
                    </span>
                </a>
            </span>
        </div>

        <table class='table'>
            <thead>
                <tr>
                    <th>{l s='ID' d='Admin.Global'}</th>
                    <th>{l s='Name' d='Modules.Agpaymentmodes.Admin'}</th>
                    <th>{l s='Additional information' d='Modules.Agpaymentmodes.Admin'}</th>
                    <th>{l s='Discount' d='Modules.Agpaymentmodes.Admin'}</th>
                    <th>{l s='Label' d='Modules.Agpaymentmodes.Admin'}</th>
                    <th>{l s='Image' d='Modules.Agpaymentmodes.Admin'}</th>
                    <th>{l s='Confirmation message' d='Modules.Agpaymentmodes.Admin'}</th>
                    <th>{l s='Default status' d='Modules.Agpaymentmodes.Admin'}</th>
                    <th>{l s='Enabled' d='Modules.Agpaymentmodes.Admin'}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$rows item=row}
                <tr>
                    <td>{$row->id}</td>
                    <td>{$row->name}</td>

                    <td>{$row->additional_info}</td>
                    <td>{$row->price_variation}</td>
                    <td>{$row->input_label}</td>
                    <td>{$row->image}</td>
                    <td>{$row->confirmation_message}</td>
                    <td>{$row->mapped_status}</td>
                    <td>
                        {if $row->enabled}
                        <i class="icon-check status"></i>
                        {else}
                        <i class="icon-times status"></i>
                        {/if}
                    </td>
                    <td>
                        <a class="btn btn-default" href="{$form_action|escape:'htmlall':'utf-8'}{$form_action_sep}{if isset($smarty.get._token)}_token={$smarty.get._token|escape:'url'}&{/if}action=edit&id_agpaymentmode={$row->id}">
                            <i class="icon icon-edit"></i>
                            {l s='Edit' d='Admin.Actions'}
                        </a>
                        <a class="btn btn-danger agpm-delete" href="{$form_action|escape:'htmlall':'utf-8'}{$form_action_sep}{if isset($smarty.get._token)}_token={$smarty.get._token|escape:'url'}&{/if}action=remove&id_agpaymentmode={$row->id}">
                            <i class="icon icon-trash"></i>
                            {l s='Delete' d='Admin.Actions'}
                        </a>
                    </td>

                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</form>