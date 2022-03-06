 
 {if ($type == 'intro')} 
 {literal}
 <div class=afsa_account_form> 
 {/literal}
 {/if}

{literal}
 <div class=afsa_logo_container>
 <img class=afsa_logo src="{/literal}{$img.logo|escape:'htmlall':'UTF-8'}{literal}">
 <div class=afsa_intro_title>
 {/literal}{$txt.configure_account|escape:'htmlall':'UTF-8'}{literal}
 </div>
 </div>
{/literal} 

 {if ($type != 'intro')} 
 {literal}
 <div class=afsa_account_form> 
 {/literal}
 {/if} 

{literal}
 <form method=post class=afsa_existing_account>
 <div class="afsa_form_help">{/literal}{$txt.existing_account_help|escape:'htmlall':'UTF-8'}{literal}</div>
 <input type="text" pattern="[0-9]{8}" maxlength="8" name="afsa_linked_account_id" value="" placeholder="{/literal}{$txt.my_account_id|escape:'htmlall':'UTF-8'}{literal}">
 <input type=hidden name=page value=afsa_settings_page>
 <input class="afsa_button" type=submit value="{/literal}{$txt.link_existing_account|escape:'htmlall':'UTF-8'}{literal}">
 </form>
 <div class=afsa_new_account>
 <div class="afsa_form_help">{/literal}{$txt.create_new_account_help|escape:'htmlall':'UTF-8'}{literal} {/literal}{$txt.create_new_account_help_more|escape:'htmlall':'UTF-8'}{literal}
 </div>
 <div class="afsa_create_account afsa_button"> 
 {/literal}{$txt.start_free_trial|escape:'htmlall':'UTF-8'}{literal}
 </div>
 </div>
 </div>
     <script type="text/javascript">     
            {/literal}{$jsCode nofilter}{literal}
    </script>
 {/literal}