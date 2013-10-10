<div class='media-page-container'>
	<h1>{$Title}</strong></h1>
	<% if Images.First %>
		<p class='media-page-main-image'>
			<span><a href='$Images.First.Link'>{$Images.First.CroppedImage(200, 200)}</a></span>
		</p>
	<% end_if %>
	<div class='media-page-date'><em>{$Date.Nice}</em></div>
	<div class='media-page-attributes'>
		<% loop MediaAttributes %>
			<% if $Content %>
				<div class='media-page-attribute {$templateClass}'><em><strong>{$Title}: </strong>{$Content}</em></div>
			<% end_if %>
		<% end_loop %>
		<br>
	</div>
	{$Content}
	<% if Images.Count > 1 %>
		<p class='media-page-images'>
			<% loop Images %>
				<% if not First %>
					<span><a href='$Link'>{$CroppedImage(100, 100)}</a></span>
				<% end_if %>
			<% end_loop %>
		</p>
	<% end_if %>
	<% if Attachments %>
		<div class='media-page-attachments'>
			<em><strong>Attachments:</strong></em>
				<% loop Attachments %>
					<div><a href='{$Link}'>{$Title}</a></div>
				<% end_loop %>
		</div>
		<br>
	<% end_if %>
	<% if Tags %>
		<div class='media-page-tags'>
			<em><strong>Tags:</strong></em>
				<% loop Tags %>
					<span><em>{$Title}</em></span>
				<% end_loop %>
		</div>
	<% end_if %>
</div>
