<?php
function local_akt_before_footer(){
$output = '
<style>
.fp-upload-form  .fp-setlicense{
    display:none;
}
</style>
';
return $output;
}