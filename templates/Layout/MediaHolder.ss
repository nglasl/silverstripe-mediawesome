<h1>{$Title}</h1>
<% if allChildren %>
	<div>
		<% if checkMediaHolder %>
			<% loop checkMediaHolder %>
				<div>
					<h4><strong><a href='{$Link}'>{$Title}</a></strong></h4>
					<div>{$Content.Summary}</div>
					<br>
				</div>
			<% end_loop %>
		<% else %>
			<% loop allChildren.limit(5).reverse %>
				<div>
					<h4><strong><a href='<% if $External %>{$External}<% else %>{$Link}<% end_if %>'>{$Title}</a></strong></h4>
					<div><em>{$Date.Nice}</em></div>
					<% if $Abstract %>
						<div>{$Abstract}</div>
					<% end_if %>
					<br>
				</div>
			<% end_loop %>
		<% end_if %>
	</div>
<% else %>
	<p>There is currently no media available.</p>
<% end_if %>