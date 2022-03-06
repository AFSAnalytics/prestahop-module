{if ($is_demo)} 
{include file='./demo.notice.tpl'}
{/if}

{literal}
<div id=afsa_container></div>
<div class="afsa_requested_widget afsa_widget_topmenubar" data-type="topmenubar"></div>
{/literal}

{if (!empty($widget.info))}
{literal}
<div id=afsa_col_infos>
<div class="afsa_requested_widget afsa_widget_config" data-type="config"></div>
</div>
{/literal}
{/if}

{literal}
<div id=afsa_col_widgets>
            
</div>

<script type="text/javascript">     
{/literal}{$jsCode.dashboard nofilter}{literal}
</script>
{/literal}

{foreach $script.dashboard as $script}
{literal}
<script src="{/literal}{$url.api_home|escape:'htmlall':'UTF-8'}{literal}/assets/js/common/v2/{/literal}{$script|escape:'htmlall':'UTF-8'}{literal}.js"></script>
{/literal}
{/foreach}

{literal}<script src="{/literal}{$url.js_dashboard|escape:'htmlall':'UTF-8'}{literal}"></script>{/literal}