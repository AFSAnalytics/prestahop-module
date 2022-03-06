{literal}
<div id=afsa_demo_notice>
<div class=afsa_logo_container>
<img class=afsa_logo src="{/literal}{$img.logo|escape:'htmlall':'UTF-8'}{literal}">
<div class=afsa_form>
<div class="afsa_create_account afsa_button">{/literal}{$txt.create_your_own_account|escape:'htmlall':'UTF-8'}{literal}</div>
</div>
</div>
<div class=afsa_content>
<div class=afsa_headline>{/literal}{$txt.demo_notice_title|escape:'htmlall':'UTF-8'}{literal}</div>
<div class=afsa_text><p>{/literal}{$txt.demo_notice_help|escape:'htmlall':'UTF-8'}{literal}</p>
<p>{/literal}{$txt.demo_notice_help_more|escape:'htmlall':'UTF-8'}{literal}</p>
</div>
</div>
</div>
<script type="text/javascript">     
{/literal}{$jsCode.demo nofilter}{literal}
</script>
{/literal}