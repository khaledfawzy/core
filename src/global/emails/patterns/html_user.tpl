<p>
  {$LANG.text_email_template_thanks}
</p>

<table cellpadding="0" cellspacing="1">
{foreach from=$fields item=field}
{if $field.is_system_field == "yes"}
{if $field.col_name == "submission_id"}
  <tr>
    <td style="font-weight: bold">{$field.field_title}</td>
    <td>{literal}{$SUBMISSIONID}{/literal}</td>
  </tr>
{elseif $field.col_name == "last_modified_date"}
  <tr>
    <td style="font-weight: bold">{$LANG.phrase_last_modified}</td>
    <td>{literal}{$LASTMODIFIEDDATE}{/literal}</td>
  </tr>
{elseif $field.col_name == "ip_address"}
  <tr>
    <td style="font-weight: bold">{$LANG.phrase_ip_address}</td>
    <td>{literal}{$IPADDRESS}{/literal}</td>
  </tr>
{/if}
{elseif $field.is_file_field == "yes"}
  <tr>
    <td style="font-weight: bold">{$field.field_title}</td>
    <td>{literal}{display_files format="html" files=$FILENAMES_{/literal}{$field.field_name}{literal} folder=$FOLDERURL_{/literal}{$field.field_name}{literal}}{/literal}</td>
  </tr>
{else}
  <tr>
    <td style="font-weight: bold">{$field.field_title}</td>
    <td>{literal}{$ANSWER_{/literal}{$field.field_name}{literal}}{/literal}</td>
  </tr>
{/if}
{/foreach}
</table>

<p>
  {$LANG.phrase_submission_made}
</p>