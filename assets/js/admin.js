(function($) {
    'use strict';
    
    const WHPAdmin = {
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Add source form
            $('#whp-add-source-form').on('submit', this.addSource.bind(this));
            
            // Delete source
            $('.whp-delete-source').on('click', this.deleteSource.bind(this));
            
            // Toggle source status
            $('.whp-toggle-source').on('click', this.toggleSource.bind(this));
            
            // Run scrape
            $('.whp-run-scrape').on('click', this.runScrape.bind(this));
            
            // Test scrape
            $('.whp-test-scrape').on('click', this.testScrape.bind(this));
            
            // Quick scrape button
            $('#whp-quick-scrape').on('click', function() {
                var url = prompt('Enter URL to scrape:');
                if (url) {
                    WHPAdmin.quickScrape(url);
                }
            });
        },
        
        addSource: function(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const button = form.find('button[type="submit"]');
            const data = form.serialize();
            
            $.ajax({
                url: whp_ajax.ajax_url,
                type: 'POST',
                data: data + '&action=whp_add_source&nonce=' + whp_ajax.nonce,
                beforeSend: function() {
                    button.prop('disabled', true).text(whp_ajax.strings.processing);
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                        window.location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    button.prop('disabled', false).text('Add Source');
                }
            });
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
                        button.closest('tr').fadeOut(400, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error: ' + response.data);
                    }
                }
            });
        },
        
        toggleSource: function(e) {
            e.preventDefault();
            
            const button = $(e.target);
            const sourceId = button.data('source-id');
            const currentStatus = button.data('status');
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            $.ajax({
                url: whp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'whp_toggle_source',
                    source_id: sourceId,
                    status: newStatus,
                    nonce: whp_ajax.nonce
                },
                beforeSend: function() {
                    button.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        button.data('status', newStatus);
                        button.text(newStatus === 'active' ? 'Deactivate' : 'Activate');
                        
                        // Update status badge
                        const badge = button.closest('tr').find('.whp-source-status');
                        badge.removeClass('whp-source-active whp-source-inactive')
                             .addClass('whp-source-' + newStatus)
                             .text(newStatus);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        },
        
        runScrape: function(e) {
            e.preventDefault();
            
            const button = $(e.target);
            const sourceId = button.data('source-id');
            
            if (!confirm('Start scraping this source now?')) {
                return;
            }
            
            $.ajax({
                url: whp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'whp_run_scrape',
                    source_id: sourceId,
                    nonce: whp_ajax.nonce
                },
                beforeSend: function() {
                    button.prop('disabled', true).text(whp_ajax.strings.scraping);
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    button.prop('disabled', false).text('Scrape Now');
                }
            });
        },
        
        testScrape: function(e) {
            e.preventDefault();
            
            const button = $(e.target);
            const sourceId = button.data('source-id');
            
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
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Scrape');
                }
            });
        },
        
        quickScrape: function(url) {
            $.ajax({
                url: whp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'whp_test_scrape',
                    url: url,
                    nonce: whp_ajax.nonce
                },
                beforeSend: function() {
                    $('#whp-quick-scrape').prop('disabled', true).text('Scraping...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Found ' + response.data.count + ' potential posts');
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
        WHPAdmin.init();
    });
    
})(jQuery);