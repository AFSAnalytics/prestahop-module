{literal}
<section id=afsa_dashboard class="afsa_main afsa_bo_dashboard">
    <div class="afsa_requested_widget afsa_widget_Overview" data-type="Overview"></div>
</section>
{/literal}

{foreach $script.dashboard as $script}
{literal}
<script src="{/literal}{$url.api_home|escape:'htmlall':'UTF-8'}{literal}/assets/js/common/v2/{/literal}{$script|escape:'htmlall':'UTF-8'}{literal}.js"></script>
{/literal}
{/foreach}

{literal}
 <script type="text/javascript"> 
    {/literal}{$jsCode.dashboard nofilter}{literal}
</script>
<script src="{/literal}{$url.js_dashboard|escape:'htmlall':'UTF-8'}{literal}"></script>'

{/literal}