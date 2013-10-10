<div class='media-holder-container'>
	<h1>{$Title}</h1>
	<p>{$Content}</p>
	<% if AllChildren %>
		<div class='media-holder-children'>
			<% if checkMediaHolder %>
				<% loop checkMediaHolder %>
					<div class='media-holder'>
						<h4><strong><a href='{$Link}'>{$Title}</a></strong></h4>
						<div>{$Content.Summary}</div>
						<br>
					</div>
				<% end_loop %>
			<% else %>
				<% loop getPaginatedChildren %>
					<div class='media-page'>
						<h4><strong><a href='<% if $ExternalLink %>{$ExternalLink}<% else_if not $Content && Attachments.count == 1 %>$Attachments.first.Link<% else %>{$Link}<% end_if %>'<% if $ExternalLink %> target='_blank'<% end_if %>>{$Title}</a></strong></h4>
						<p class='media-date'><em>{$Date.Date}</em></p>
						<% if $Abstract %>
							<div class='media-abstract'>{$Abstract}</div>
						<% end_if %>
						<br>
					</div>
				<% end_loop %>
				<% if getPaginatedChildren.MoreThanOnePage %>
					<div class='media-pagination'>
						<% if getPaginatedChildren.NotFirstPage %>
							<span class='media-pagination-previous'><a href='{$getPaginatedChildren.PrevLink}'>&#60; Previous</a></span>
						<% end_if %>
						<% loop getPaginatedChildren.Pages %>
							<% if $CurrentBool %>
								<span class='media-pagination-number'>{$PageNum}</span>
							<% else %>
								<span class='media-pagination-number-link'><a href='{$Link}'>{$PageNum}</a></span>
							<% end_if %>
						<% end_loop %>
						<% if getPaginatedChildren.NotLastPage %>
							<span class='media-pagination-next'><a href='{$getPaginatedChildren.NextLink}'>Next &#62;</a></span>
						<% end_if %>
					</div>
				<% end_if %>
			<% end_if %>
		</div>
	<% else %>
		<p class='no-media'>There is currently no media available.</p>
	<% end_if %>
</div>
