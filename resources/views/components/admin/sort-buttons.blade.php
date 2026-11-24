@props(['action', 'first' => false, 'last' => false])
<x-admin.action-button :action="$action" icon="arrowUp" label="Naikkan urutan" :disabled="$first"><input type="hidden" name="direction" value="up"></x-admin.action-button>
<x-admin.action-button :action="$action" icon="arrowDown" label="Turunkan urutan" :disabled="$last"><input type="hidden" name="direction" value="down"></x-admin.action-button>
