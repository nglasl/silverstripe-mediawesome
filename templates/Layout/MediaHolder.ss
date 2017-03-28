<div class='media-holder-container'>
	<h1>{$Title}</h1>
	<div class='media-holder-content'>{$Content}</div>
	<% if $AllChildren %>
		<div class='media-holder-children'>
			<% if $MediaHolderChildren %>
				<% loop $MediaHolderChildren %>
					<div class='media-holder'>
						<h2><a href='{$Link}'><strong>{$Title}</strong></a></h2>
						<div>{$Content.Summary}</div>
						<br>
					</div>
				<% end_loop %>
			<% else %>
				<% loop $PaginatedChildren %>
					<div class='media-page'>
						<h2><a href='<% if $ExternalLink %>{$ExternalLink}<% else_if not $Content && $Attachments.count == 1 %>$Attachments.first.Link<% else %>{$Link}<% end_if %>'<% if $ExternalLink %> target='_blank'<% end_if %>><strong>{$Title}</strong></a></h2>
						<p class='media-date'><em>{$Date.Format('M j, Y')}</em></p>
						<% if $Abstract %>
							<div class='media-abstract'>{$Abstract}</div>
						<% end_if %>
						<br>
					</div>
				<% end_loop %>
				<% if $PaginatedChildren.MoreThanOnePage %>
					<div class='media-pagination'>
						<% if $PaginatedChildren.NotFirstPage %>
							<span class='media-pagination-previous'><a href='{$PaginatedChildren.PrevLink}'>&laquo;&nbsp;Previous</a></span>
						<% end_if %>
						<% loop $PaginatedChildren.Pages %>
							<% if $CurrentBool %>
								<span class='media-pagination-number current'>{$PageNum}</span>
							<% else %>
								<span class='media-pagination-number'><a href='{$Link}'>{$PageNum}</a></span>
							<% end_if %>
						<% end_loop %>
						<% if $PaginatedChildren.NotLastPage %>
							<span class='media-pagination-next'><a href='{$PaginatedChildren.NextLink}'>Next&nbsp;&raquo;</a></span>
						<% end_if %>
					</div>
				<% end_if %>
				<div class='media-filter'>{$DateFilterForm}</div>
			<% end_if %>
		</div>
	<% else %>
		<p class='no-media'>There is currently no media available.</p>
	<% end_if %>
	{$Form}
</div>
