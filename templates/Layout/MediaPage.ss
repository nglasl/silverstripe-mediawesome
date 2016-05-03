<div class='media-page-container'>
	<h1>{$Title}</h1>
	<% if $Images.first %>
		<p class='media-page-main-image'>
			<span><a href='$Images.first.Link'>{$Images.first.Fill(200, 200)}</a></span>
		</p>
	<% end_if %>
	<% if $Categories %>
		<h3 class='media-page-categories'>
			<% loop $Categories %>
				<span><a href='{$Up.getParent.Link}?category={$Title.URLATT}'>{$Title}</a></span>
			<% end_loop %>
		</h3>
	<% end_if %>
	<div class='media-page-date'><em>{$Date.Date}</em></div>
	<div class='media-page-attributes'>
		<% loop $Attributes %>
			<% if $Content %>
				<div class='media-page-attribute {$TemplateClass}'><em><strong>{$Title}: </strong>{$Content}</em></div>
			<% end_if %>
		<% end_loop %>
		<br>
	</div>
	<div class='media-page-content'>{$Content}</div>
	<% if $Images.count > 1 %>
		<p class='media-page-images'>
			<% loop $Images %>
				<% if not $first %>
					<span><a href='{$Link}'>{$Fill(100, 100)}</a></span>
				<% end_if %>
			<% end_loop %>
		</p>
	<% end_if %>
	<% if $Attachments %>
		<div class='media-page-attachments'>
			<em><strong>Attachments:</strong></em>
			<% loop $Attachments %>
				<div><a href='{$Link}'>{$Title}</a></div>
			<% end_loop %>
		</div>
		<br>
	<% end_if %>
	<% if $Tags %>
		<div class='media-page-tags'>
			<em><strong>Tags:</strong></em>
			<% loop $Tags %>
				<span><a href='{$Up.getParent.Link}?tag={$Title.URLATT}'><em>{$Title}</em></a></span>
			<% end_loop %>
		</div>
	<% end_if %>
	{$Form}
</div>
