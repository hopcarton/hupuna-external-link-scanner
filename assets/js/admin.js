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
            
            if (window.scanResults) {
                displayResults(window.scanResults);
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
                if (currentTab === 'grouped') {
                    displayGroupedResults(data.grouped);
                } else {
                    displayAllResults(data.results);
                }
            }
            
            $results.show();
        }
        
        /**
         * Hiển thị kết quả nhóm theo URL
         */
        function displayGroupedResults(grouped) {
            var html = '';
            
            $.each(grouped, function(url, group) {
                html += '<div class="hupuna-link-group">';
                html += '<div class="hupuna-link-group-header">';
                html += '<a href="' + escapeHtml(url) + '" target="_blank" class="hupuna-link-url">' + escapeHtml(url) + '</a>';
                html += '<span class="hupuna-link-count">' + group.occurrences.length + ' occurrences</span>';
                html += '</div>';
                
                $.each(group.occurrences, function(index, item) {
                    html += '<div class="hupuna-link-item">';
                    html += '<div class="hupuna-link-item-info">';
                    html += '<span class="hupuna-link-item-type ' + item.type + '">' + item.type + '</span>';
                    html += '<div class="hupuna-link-item-title">' + escapeHtml(item.title) + '</div>';
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
            
            $resultsContent.html(html);
        }
        
        /**
         * Hiển thị tất cả kết quả
         */
        function displayAllResults(results) {
            var html = '';
            
            $.each(results, function(index, item) {
                html += '<div class="hupuna-link-item">';
                html += '<div class="hupuna-link-item-info">';
                html += '<span class="hupuna-link-item-type ' + item.type + '">' + item.type + '</span>';
                html += '<div class="hupuna-link-item-title">' + escapeHtml(item.title) + '</div>';
                html += '<div class="hupuna-link-item-text">URL: <a href="' + escapeHtml(item.url) + '" target="_blank">' + escapeHtml(item.url) + '</a></div>';
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
            
            $resultsContent.html(html);
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

