(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Khai báo biến
        var $scanButton = $('#hupuna-scan-button');
        var $scanStatus = $('#hupuna-scan-status');
        var $results = $('#hupuna-scan-results');
        var $resultsContent = $('#hupuna-results-content');
        var $tabButtons = $('.tab-button');
        var currentTab = 'grouped';
        var currentPage = 1;
        var itemsPerPage = 20;
        
        // Xử lý click nút quét
        $scanButton.on('click', function() {
            if ($(this).hasClass('scanning')) {
                return;
            }
            
            $(this).addClass('scanning').prop('disabled', true);
            $scanStatus.text(hupunaEls.scanning).addClass('scanning');
            $results.hide();
            
            $.ajax({
                url: hupunaEls.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hupuna_scan_links',
                    nonce: hupunaEls.nonce
                },
                success: function(response) {
                    if (response.success) {
                        displayResults(response.data);
                        $scanStatus.text(hupunaEls.completed).removeClass('scanning').addClass('completed');
                    } else {
                        alert('Error: ' + (response.data.message || 'Unable to scan links.'));
                        $scanStatus.text('').removeClass('scanning completed');
                    }
                },
                error: function() {
                    alert('Error: Unable to connect to server.');
                    $scanStatus.text('').removeClass('scanning completed');
                },
                complete: function() {
                    $scanButton.removeClass('scanning').prop('disabled', false);
                }
            });
        });
        
        // Xử lý chuyển tab
        $tabButtons.on('click', function() {
            var tab = $(this).data('tab');
            $tabButtons.removeClass('active');
            $(this).addClass('active');
            currentTab = tab;
            currentPage = 1;
            
            if (window.scanResults) {
                displayResults(window.scanResults);
            }
        });
        
        // Xử lý thay đổi items per page
        $(document).on('change', '#hupuna-items-per-page', function() {
            itemsPerPage = parseInt($(this).val());
            currentPage = 1;
            if (window.scanResults) {
                displayResults(window.scanResults);
            }
        });
        
        // Xử lý phân trang
        $(document).on('click', '.hupuna-pagination a', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            if (page) {
                currentPage = page;
                if (window.scanResults) {
                    displayResults(window.scanResults);
                }
            }
        });
        
        /**
         * Hiển thị kết quả quét
         */
        function displayResults(data) {
            window.scanResults = data;
            
            $('#total-links').text(data.total);
            $('#unique-links').text(data.unique);
            
            if (data.total === 0) {
                $resultsContent.html(
                    '<div class="hupuna-no-results">' +
                    '<span class="dashicons dashicons-yes-alt"></span>' +
                    '<p><strong>No external links found!</strong></p>' +
                    '<p>All links point to the current domain.</p>' +
                    '</div>'
                );
            } else {
                // Thêm controls phân trang
                var paginationControls = '<div class="hupuna-pagination-controls">' +
                    '<label>Items per page: ' +
                    '<select id="hupuna-items-per-page">' +
                    '<option value="10"' + (itemsPerPage === 10 ? ' selected' : '') + '>10</option>' +
                    '<option value="20"' + (itemsPerPage === 20 ? ' selected' : '') + '>20</option>' +
                    '<option value="50"' + (itemsPerPage === 50 ? ' selected' : '') + '>50</option>' +
                    '<option value="100"' + (itemsPerPage === 100 ? ' selected' : '') + '>100</option>' +
                    '</select>' +
                    '</label>' +
                    '</div>';
                
                if (currentTab === 'grouped') {
                    displayGroupedResults(data.grouped, paginationControls);
                } else {
                    displayAllResults(data.results, paginationControls);
                }
            }
            
            $results.show();
        }
        
        /**
         * Hiển thị kết quả nhóm theo URL
         */
        function displayGroupedResults(grouped, paginationControls) {
            var html = paginationControls;
            
            // Chuyển grouped object thành array để phân trang
            var groupsArray = [];
            $.each(grouped, function(url, group) {
                groupsArray.push({url: url, group: group});
            });
            
            var totalGroups = groupsArray.length;
            var totalPages = Math.ceil(totalGroups / itemsPerPage);
            var startIndex = (currentPage - 1) * itemsPerPage;
            var endIndex = startIndex + itemsPerPage;
            var paginatedGroups = groupsArray.slice(startIndex, endIndex);
            
            $.each(paginatedGroups, function(index, item) {
                var url = item.url;
                var group = item.group;
                
                html += '<div class="hupuna-link-group">';
                html += '<div class="hupuna-link-group-header">';
                html += '<a href="' + escapeHtml(url) + '" target="_blank" class="hupuna-link-url">' + escapeHtml(url) + '</a>';
                html += '<span class="hupuna-link-count">' + group.occurrences.length + ' occurrences</span>';
                html += '</div>';
                
                $.each(group.occurrences, function(index, item) {
                    html += '<div class="hupuna-link-item">';
                    html += '<div class="hupuna-link-item-info">';
                    html += '<span class="hupuna-link-item-type ' + item.type + '">' + item.type + '</span>';
                    if (item.tag) {
                        html += '<span class="hupuna-link-tag">' + escapeHtml(item.tag) + '</span>';
                    }
                    html += '<div class="hupuna-link-item-title">' + escapeHtml(item.title) + '</div>';
                    if (item.location) {
                        var locationText = '';
                        switch(item.location) {
                            case 'content':
                                locationText = 'Location: Post Content';
                                break;
                            case 'excerpt':
                                locationText = 'Location: Excerpt';
                                break;
                            case 'meta':
                                locationText = 'Location: Custom Field' + (item.meta_key ? ' (' + escapeHtml(item.meta_key) + ')' : '');
                                break;
                        }
                        if (locationText) {
                            html += '<div class="hupuna-link-item-text" style="color: #d63638; font-weight: 600;">' + locationText + '</div>';
                        }
                    }
                    if (item.tag && item.attribute) {
                        html += '<div class="hupuna-link-item-text">Tag: &lt;' + escapeHtml(item.tag) + '&gt; - Attribute: ' + escapeHtml(item.attribute) + '</div>';
                    }
                    if (item.link_text) {
                        html += '<div class="hupuna-link-item-text">Text: "' + escapeHtml(item.link_text) + '"</div>';
                    }
                    html += '</div>';
                    html += '<div class="hupuna-link-item-actions">';
                    if (item.view_url) {
                        html += '<a href="' + escapeHtml(item.view_url) + '" target="_blank" class="hupuna-link-button view">View</a>';
                    }
                    if (item.edit_url) {
                        html += '<a href="' + escapeHtml(item.edit_url) + '" class="hupuna-link-button edit">Edit</a>';
                    }
                    html += '<a href="' + escapeHtml(item.url) + '" target="_blank" class="hupuna-link-button external">Open Link</a>';
                    html += '</div>';
                    html += '</div>';
                });
                
                html += '</div>';
            });
            
            // Thêm phân trang
            html += generatePagination(totalPages, currentPage, totalGroups, startIndex + 1, Math.min(endIndex, totalGroups));
            
            $resultsContent.html(html);
        }
        
        /**
         * Hiển thị tất cả kết quả
         */
        function displayAllResults(results, paginationControls) {
            var html = paginationControls;
            
            var totalItems = results.length;
            var totalPages = Math.ceil(totalItems / itemsPerPage);
            var startIndex = (currentPage - 1) * itemsPerPage;
            var endIndex = startIndex + itemsPerPage;
            var paginatedResults = results.slice(startIndex, endIndex);
            
            $.each(paginatedResults, function(index, item) {
                html += '<div class="hupuna-link-item">';
                html += '<div class="hupuna-link-item-info">';
                html += '<span class="hupuna-link-item-type ' + item.type + '">' + item.type + '</span>';
                if (item.tag) {
                    html += '<span class="hupuna-link-tag">' + escapeHtml(item.tag) + '</span>';
                }
                html += '<div class="hupuna-link-item-title">' + escapeHtml(item.title) + '</div>';
                html += '<div class="hupuna-link-item-text">URL: <a href="' + escapeHtml(item.url) + '" target="_blank">' + escapeHtml(item.url) + '</a></div>';
                if (item.location) {
                    var locationText = '';
                    switch(item.location) {
                        case 'content':
                            locationText = 'Location: Post Content';
                            break;
                        case 'excerpt':
                            locationText = 'Location: Excerpt';
                            break;
                        case 'meta':
                            locationText = 'Location: Custom Field' + (item.meta_key ? ' (' + escapeHtml(item.meta_key) + ')' : '');
                            break;
                    }
                    if (locationText) {
                        html += '<div class="hupuna-link-item-text" style="color: #d63638; font-weight: 600;">' + locationText + '</div>';
                    }
                }
                if (item.tag && item.attribute) {
                    html += '<div class="hupuna-link-item-text">Tag: &lt;' + escapeHtml(item.tag) + '&gt; - Attribute: ' + escapeHtml(item.attribute) + '</div>';
                }
                if (item.link_text) {
                    html += '<div class="hupuna-link-item-text">Text: "' + escapeHtml(item.link_text) + '"</div>';
                }
                html += '</div>';
                html += '<div class="hupuna-link-item-actions">';
                if (item.view_url) {
                    html += '<a href="' + escapeHtml(item.view_url) + '" target="_blank" class="hupuna-link-button view">View</a>';
                }
                if (item.edit_url) {
                    html += '<a href="' + escapeHtml(item.edit_url) + '" class="hupuna-link-button edit">Edit</a>';
                }
                html += '<a href="' + escapeHtml(item.url) + '" target="_blank" class="hupuna-link-button external">Open Link</a>';
                html += '</div>';
                html += '</div>';
            });
            
            // Thêm phân trang
            html += generatePagination(totalPages, currentPage, totalItems, startIndex + 1, Math.min(endIndex, totalItems));
            
            $resultsContent.html(html);
        }
        
        /**
         * Tạo HTML phân trang
         */
        function generatePagination(totalPages, currentPage, totalItems, startItem, endItem) {
            if (totalPages <= 1) {
                return '<div class="hupuna-pagination-info">Showing ' + totalItems + ' result' + (totalItems !== 1 ? 's' : '') + '</div>';
            }
            
            var html = '<div class="hupuna-pagination-wrapper">';
            html += '<div class="hupuna-pagination-info">Showing ' + startItem + ' to ' + endItem + ' of ' + totalItems + ' results</div>';
            html += '<div class="hupuna-pagination">';
            
            // Previous button
            if (currentPage > 1) {
                html += '<a href="#" class="hupuna-page-link" data-page="' + (currentPage - 1) + '">&laquo; Previous</a>';
            } else {
                html += '<span class="hupuna-page-link disabled">&laquo; Previous</span>';
            }
            
            // Page numbers
            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                html += '<a href="#" class="hupuna-page-link" data-page="1">1</a>';
                if (startPage > 2) {
                    html += '<span class="hupuna-page-ellipsis">...</span>';
                }
            }
            
            for (var i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    html += '<span class="hupuna-page-link current">' + i + '</span>';
                } else {
                    html += '<a href="#" class="hupuna-page-link" data-page="' + i + '">' + i + '</a>';
                }
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += '<span class="hupuna-page-ellipsis">...</span>';
                }
                html += '<a href="#" class="hupuna-page-link" data-page="' + totalPages + '">' + totalPages + '</a>';
            }
            
            // Next button
            if (currentPage < totalPages) {
                html += '<a href="#" class="hupuna-page-link" data-page="' + (currentPage + 1) + '">Next &raquo;</a>';
            } else {
                html += '<span class="hupuna-page-link disabled">Next &raquo;</span>';
            }
            
            html += '</div>';
            html += '</div>';
            
            return html;
        }
        
        /**
         * Escape HTML để tránh XSS
         */
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    });
})(jQuery);

