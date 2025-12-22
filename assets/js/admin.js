(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Elements
        var $scanButton = $('#hupuna-scan-button');
        var $progressWrap = $('#hupuna-progress-wrap');
        var $progressFill = $('.hupuna-progress-fill');
        var $progressText = $('#hupuna-progress-text');
        var $results = $('#hupuna-scan-results');
        var $resultsContent = $('#hupuna-results-content');
        
        // State
        var allResults = [];
        var isScanning = false;
        var scanQueue = [];
        var totalStepsInitial = 0;
        
        // Pagination & Tabs
        var currentTab = 'grouped';
        var currentPage = 1;
        var itemsPerPage = 20;
        
        // --- Core Scanning Logic ---
        
        function buildScanQueue() {
            var queue = [];
            
            // 1. Post Types
            var postTypes = hupunaEls.postTypes; 
            if (!Array.isArray(postTypes)) {
                postTypes = Object.values(postTypes);
            }
            
            $.each(postTypes, function(i, type) {
                queue.push({
                    step: 'post_type',
                    sub_step: type,
                    page: 1,
                    label: 'Scanning Post Type: ' + type
                });
            });
            
            // 2. Comments
            queue.push({ step: 'comment', page: 1, label: 'Scanning Comments...' });
            
            // 3. Options
            queue.push({ step: 'option', page: 1, label: 'Scanning Options...' });
            
            return queue;
        }
        
        $scanButton.on('click', function() {
            if (isScanning) return;
            
            isScanning = true;
            allResults = [];
            $scanButton.prop('disabled', true).text('Scanning...');
            $results.hide();
            $progressWrap.show();
            
            scanQueue = buildScanQueue();
            totalStepsInitial = scanQueue.length;
            
            processQueue();
        });
        
        function processQueue() {
            if (scanQueue.length === 0) {
                finishScan();
                return;
            }
            
            var currentTask = scanQueue[0];
            var progressPercent = 100 - ((scanQueue.length / totalStepsInitial) * 100);
            if (progressPercent < 2) progressPercent = 2;
            
            updateProgress(progressPercent, currentTask.label + ' (Page ' + currentTask.page + ')');
            
            $.ajax({
                url: hupunaEls.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'hupuna_scan_batch',
                    nonce: hupunaEls.nonce,
                    step: currentTask.step,
                    sub_step: currentTask.sub_step || '',
                    page: currentTask.page
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.results && response.data.results.length > 0) {
                            allResults = allResults.concat(response.data.results);
                        }
                        
                        if (response.data.done) {
                            scanQueue.shift(); // Task complete
                        } else {
                            scanQueue[0].page++; // Next page
                        }
                        
                        processQueue(); // Recursive call
                        
                    } else {
                        handleError(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    handleError('Server connection failed: ' + error);
                }
            });
        }
        
        function handleError(msg) {
            console.error('Scan Error:', msg);
            alert('Error: ' + msg);
            isScanning = false;
            $scanButton.prop('disabled', false).text('Start Scan');
            $progressText.text('Error encountered.');
        }
        
        function updateProgress(percent, text) {
            $progressFill.css('width', percent + '%');
            $progressText.text(text);
        }
        
        function finishScan() {
            isScanning = false;
            updateProgress(100, 'Scan Completed!');
            $scanButton.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Start Scan');
            displayResults(allResults);
        }

        // --- UI & Display Logic ---
        
        $('.tab-button').on('click', function() {
            $('.tab-button').removeClass('active');
            $(this).addClass('active');
            currentTab = $(this).data('tab');
            currentPage = 1;
            renderCurrentPage();
        });
        
        function displayResults(data) {
            window.rawResults = data;
            
            // Group by URL
            window.groupedResults = {};
            $.each(data, function(i, item) {
                if (!window.groupedResults[item.url]) {
                    window.groupedResults[item.url] = { url: item.url, occurrences: [] };
                }
                window.groupedResults[item.url].occurrences.push(item);
            });
            
            $('#total-links').text(data.length);
            $('#unique-links').text(Object.keys(window.groupedResults).length);
            
            $results.show();
            renderCurrentPage();
        }
        
        function renderCurrentPage() {
            var html = '';
            var list = [];
            
            if (currentTab === 'grouped') {
                list = Object.values(window.groupedResults);
            } else {
                list = window.rawResults;
            }
            
            if (list.length === 0) {
                $resultsContent.html('<div class="hupuna-no-results">No external links found. Great job!</div>');
                return;
            }
            
            // Client-side Pagination
            var totalItems = list.length;
            var totalPages = Math.ceil(totalItems / itemsPerPage);
            var start = (currentPage - 1) * itemsPerPage;
            var end = start + itemsPerPage;
            var pageItems = list.slice(start, end);
            
            // Render List
            if (currentTab === 'grouped') {
                $.each(pageItems, function(i, group) {
                    html += '<div class="hupuna-link-group">';
                    html += '<div class="hupuna-link-group-header"><strong>' + escapeHtml(group.url) + '</strong> <span class="hupuna-link-count">' + group.occurrences.length + '</span></div>';
                    $.each(group.occurrences, function(j, item) {
                        html += renderItemRow(item);
                    });
                    html += '</div>';
                });
            } else {
                $.each(pageItems, function(i, item) {
                    html += renderItemRow(item);
                });
            }
            
            // Render Pagination
            if (totalPages > 1) {
                html += '<div class="hupuna-pagination">';
                if (currentPage > 1) html += '<button class="button" onclick="window.changeHupunaPage('+(currentPage-1)+')">&laquo; Prev</button>';
                html += '<span>Page ' + currentPage + ' of ' + totalPages + '</span>';
                if (currentPage < totalPages) html += '<button class="button" onclick="window.changeHupunaPage('+(currentPage+1)+')">Next &raquo;</button>';
                html += '</div>';
            }
            
            $resultsContent.html(html);
        }
        
        function renderItemRow(item) {
            return '<div class="hupuna-link-item">' +
                   '<span class="hupuna-link-item-type ' + item.type + '">' + item.type + '</span> ' +
                   '<div class="info">' +
                       '<div class="title">' + escapeHtml(item.title) + '</div>' +
                       '<div class="meta">Location: ' + item.location + ' | Tag: &lt;' + item.tag + '&gt;</div>' +
                   '</div>' +
                   '<div class="actions">' +
                       (item.edit_url ? '<a href="' + item.edit_url + '" target="_blank" class="button button-small">Edit</a>' : '') +
                       (item.view_url ? '<a href="' + item.view_url + '" target="_blank" class="button button-small">View</a>' : '') +
                   '</div>' +
                   '</div>';
        }
        
        window.changeHupunaPage = function(page) {
            currentPage = page;
            renderCurrentPage();
        };
        
        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/[&<>"']/g, function(m) { 
                return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[m]; 
            });
        }
    });
})(jQuery);