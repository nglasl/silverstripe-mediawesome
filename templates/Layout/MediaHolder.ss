<h1>{$Title}</h1>
<p>{$Content}</p>
<% if AllChildren %>
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
			<% loop getPaginatedChildren %>
				<div>
					<h4><strong><a href='<% if $External %>{$External}<% else_if not $Content && Attachments.count == 1 %>$Attachments.first.Link<% else %>{$Link}<% end_if %>'>{$Title}</a></strong></h4>
					<div><em>{$Date.Nice}</em></div>
					<% if $Abstract %>
						<div>{$Abstract}</div>
					<% end_if %>
					<br>
				</div>
			<% end_loop %>
			<% if getPaginatedChildren.MoreThanOnePage %>
				<div>
					<% if getPaginatedChildren.NotFirstPage %>
						<span><a href='{$getPaginatedChildren.PrevLink}'>Previous</a></span>
					<% end_if %>
					<% loop getPaginatedChildren.Pages %>
						<% if $CurrentBool %>
							<span>{$PageNum}</span>
						<% else %>
							<span><a href='{$Link}'>{$PageNum}</a></span>
						<% end_if %>
					<% end_loop %>
					<% if getPaginatedChildren.NotLastPage %>
						<span><a href='{$getPaginatedChildren.NextLink}'>Next</a></span>
					<% end_if %>
				</div>
			<% end_if %>
		<% end_if %>
	</div>
<% else %>
	<p>There is currently no media available.</p>
<% end_if %>