(function($) {
    'use strict';
    
    const WHP = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Add source form
            $('#whp-add-source-form').on('submit', this.addSource.bind(this));
            
            // Test scrape
            $('.whp-test-scrape').on('click', this.testScrape.bind(this));
            
            // Delete source
            $('.whp-delete-source').on('click', this.deleteSource.bind(this));
            
            // Toggle source
            $('.whp-toggle-source').on('click', this.toggleSource.bind(this));
            
            // Quick scrape
            $('.whp-quick-scrape').on('click', this.quickScrape.bind(this));
        },
        
        addSource: function(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const data = form.serialize();
            
            $.ajax({
                url: whp_ajax.ajax_url,
                type: 'POST',
                data: data + '&action=whp_add_source&nonce=' + whp_ajax.nonce,
                beforeSend: function() {
                    form.find('button[type="submit"]').prop('disabled', true).text('Adding...');
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                complete: function() {
                    form.find('button[type="submit"]').prop('disabled', false).text('Add Source');
                }
            });
        },
        
        testScrape: function(e) {
            e.preventDefault();
            
            const button = $(e.target);
            const sourceId = button.data('source-id');
            
            if (!confirm('Test scraping will fetch URLs without importing. Continue?')) {
                return;
            }
            
            $.ajax({
                url: whp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'whp_test_scrape',
                    source_id: sourceId,
                    nonce: whp_ajax.nonce
                },
                beforeSend: function() {
                    button.prop('disabled', true).text('Testing...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Found ' + response.data.count + ' potential posts');
                        if (response.data.urls && response.data.urls.length) {
                            WHP.showTestResults(response.data.urls);
                        }
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Scrape');
                }
            });
        },
        
        showTestResults: function(urls) {
            let html = '<h3>Found URLs:</h3><ul>';
            urls.forEach(function(url) {
                html += '<li><a href="' + url + '" target="_blank">' + url + '</a></li>';
            });
            html += '</ul>';
            
            $('#whp-test-results').html(html).show();
        },
        
        deleteSource: function(e) {
            e.preventDefault();
            
            if (!confirm(whp_ajax.strings.confirm_delete)) {
                return;
            }
            
            const button = $(e.target);
            const sourceId = button.data('source-id');
            
            $.ajax({
                url: whp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'whp_delete_source',
                    source_id: sourceId,
                    nonce: whp_ajax.nonce
                },
                beforeSend: function() {
                    button.prop('disabled', true).text('Deleting...');
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('tr').fadeOut();
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        },
        
        quickScrape: function(e) {
            e.preventDefault();
            
            const url = prompt('Enter URL to scrape:');
            if (!url) return;
            
            $.ajax({
                url: whp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'whp_quick_scrape',
                    url: url,
                    nonce: whp_ajax.nonce
                },
                beforeSend: function() {
                    $('#whp-quick-scrape').prop('disabled', true).text('Scraping...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Scraping started! Check logs for details.');
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    $('#whp-quick-scrape').prop('disabled', false).text('Quick Scrape');
                }
            });
        }
    };
    
    $(document).ready(function() {
        WHP.init();
    });
    
})(jQuery);