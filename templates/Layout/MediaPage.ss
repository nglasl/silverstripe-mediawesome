<div class='media-page-container'>
	<h1>{$Title}</strong></h1>
	<div class='media-page-date'><em>{$Date.Nice}</em></div>
	<div class='media-page-attributes'>
		<% loop MediaAttributes %>
			<% if $Content %>
				<div class='media-page-attribute'><em><strong>{$Title}: </strong>{$Content}</em></div>
			<% end_if %>
		<% end_loop %>
		<br>
	</div>
	{$Content}
	<% if Images %>
		<p class='media-page-images'>
			<% loop Images %>
				<span class='media-page-image'><a href='$Link'>{$CroppedImage(100, 100)}</a></span>
			<% end_loop %>
		</p>
	<% end_if %>
	<% if Attachments %>
		<div class='media-page-attachments'>
			<em><strong>Attachments:</strong></em>
				<% loop Attachments %>
					<div class='media-page-attachment'><a href='{$Link}'>{$Title}</a></div>
				<% end_loop %>
		</div>
	<% end_if %>
</div>
