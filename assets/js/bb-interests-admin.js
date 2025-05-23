/**
 * BAE Profile Interests - Admin JavaScript
 */
(function($) {
    'use strict';

    var BAEInterestsAdmin = {
        init: function() {
            this.bindEvents();
            this.initFilters();
        },

        bindEvents: function() {
            // Interest management
            $(document).on('click', '.bae-delete-interest', this.handleDeleteInterest);
            
            // Request management
            $(document).on('click', '.bae-approve-request', this.handleApproveRequest);
            $(document).on('click', '.bae-reject-request', this.handleRejectRequest);
            
            // Modal handling
            $(document).on('click', '#bae-request-modal-submit', this.handleModalSubmit);
            $(document).on('click', '#bae-request-modal-cancel', this.hideModal);
            $(document).on('click', '#bae-request-modal', function(e) {
                if (e.target === this) {
                    BAEInterestsAdmin.hideModal();
                }
            });
            
            // Search and filters
            $(document).on('keyup', '#bae-search-interests', this.handleSearch);
            $(document).on('change', '#bae-filter-category', this.handleCategoryFilter);
        },

        initFilters: function() {
            // Initialize any existing filters
            this.handleCategoryFilter();
        },

        handleDeleteInterest: function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var interestId = $this.data('interest-id');
            var interestName = $this.data('interest-name');
            
            // Confirm deletion
            var confirmMessage = baeInterestsAdmin.confirmDelete.replace('%s', interestName);
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Show loading state
            $this.text('Deleting...');
            
            $.post(baeInterestsAdmin.ajaxUrl, {
                action: 'bae_admin_delete_interest',
                nonce: baeInterestsAdmin.nonce,
                interest_id: interestId
            }, function(response) {
                if (response.success) {
                    // Remove the row
                    $this.closest('tr').fadeOut(function() {
                        $(this).remove();
                        BAEInterestsAdmin.updateRowCount();
                    });
                    
                    BAEInterestsAdmin.showNotice(response.data.message, 'success');
                } else {
                    BAEInterestsAdmin.showNotice(response.data.message, 'error');
                    $this.text('Delete');
                }
            }).fail(function() {
                BAEInterestsAdmin.showNotice('An error occurred. Please try again.', 'error');
                $this.text('Delete');
            });
        },

        handleApproveRequest: function(e) {
            e.preventDefault();
            
            var requestId = $(this).data('request-id');
            var requestName = $(this).closest('tr').find('.column-name').text().trim();
            
            BAEInterestsAdmin.showModal('approve', requestId, requestName);
        },

        handleRejectRequest: function(e) {
            e.preventDefault();
            
            var requestId = $(this).data('request-id');
            var requestName = $(this).closest('tr').find('.column-name').text().trim();
            
            BAEInterestsAdmin.showModal('reject', requestId, requestName);
        },

        showModal: function(action, requestId, requestName) {
            var title, submitText;
            
            if (action === 'approve') {
                title = 'Approve Interest Request: ' + requestName;
                submitText = 'Approve';
            } else {
                title = 'Reject Interest Request: ' + requestName;
                submitText = 'Reject';
            }
            
            $('#bae-request-modal-title').text(title);
            $('#bae-request-modal-submit').text(submitText);
            $('#bae-request-modal-submit').data('action', action);
            $('#bae-request-modal-submit').data('request-id', requestId);
            $('#bae-request-modal-notes').val('');
            
            $('#bae-request-modal').fadeIn();
        },

        hideModal: function() {
            $('#bae-request-modal').fadeOut();
        },

        handleModalSubmit: function() {
            var $this = $(this);
            var action = $this.data('action');
            var requestId = $this.data('request-id');
            var notes = $('#bae-request-modal-notes').val();
            var $spinner = $('.bae-spinner');
            
            // Disable buttons and show spinner
            $this.prop('disabled', true);
            $('#bae-request-modal-cancel').prop('disabled', true);
            $spinner.show();
            
            var ajaxAction = action === 'approve' ? 'bae_admin_approve_interest' : 'bae_admin_reject_interest';
            
            $.post(baeInterestsAdmin.ajaxUrl, {
                action: ajaxAction,
                nonce: baeInterestsAdmin.nonce,
                request_id: requestId,
                notes: notes
            }, function(response) {
                if (response.success) {
                    // Remove request row
                    $('#request-' + requestId).fadeOut(function() {
                        $(this).remove();
                        BAEInterestsAdmin.updateRowCount();
                    });
                    
                    // Hide modal
                    BAEInterestsAdmin.hideModal();
                    
                    BAEInterestsAdmin.showNotice(response.data.message, 'success');
                } else {
                    BAEInterestsAdmin.showNotice(response.data.message, 'error');
                }
            }).fail(function() {
                BAEInterestsAdmin.showNotice('An error occurred. Please try again.', 'error');
            }).always(function() {
                // Re-enable buttons and hide spinner
                $this.prop('disabled', false);
                $('#bae-request-modal-cancel').prop('disabled', false);
                $spinner.hide();
            });
        },

        handleSearch: function() {
            var term = $(this).val().toLowerCase();
            
            if (term === '') {
                $('.bae-interest-row, .bae-request-row').show();
            } else {
                $('.bae-interest-row, .bae-request-row').each(function() {
                    var $row = $(this);
                    var name = $row.find('.column-name').text().toLowerCase();
                    
                    if (name.indexOf(term) > -1) {
                        $row.show();
                    } else {
                        $row.hide();
                    }
                });
            }
            
            BAEInterestsAdmin.updateRowCount();
        },

        handleCategoryFilter: function() {
            var category = $(this).val();
            
            if (category === '') {
                $('.bae-interest-row').show();
            } else {
                $('.bae-interest-row').hide();
                $('.bae-interest-row[data-category="' + category + '"]').show();
            }
            
            BAEInterestsAdmin.updateRowCount();
        },

        updateRowCount: function() {
            var $table = $('.bae-interests-table, .bae-requests-table');
            if ($table.length) {
                var visibleRows = $table.find('tbody tr:visible').length;
                var totalRows = $table.find('tbody tr').length;
                
                var $count = $('.bae-row-count');
                if ($count.length === 0) {
                    $table.after('<p class="bae-row-count"></p>');
                    $count = $('.bae-row-count');
                }
                
                if (visibleRows !== totalRows) {
                    $count.text('Showing ' + visibleRows + ' of ' + totalRows + ' items');
                } else {
                    $count.text(totalRows + ' items');
                }
            }
        },

        showNotice: function(message, type) {
            type = type || 'info';
            var noticeClass = 'notice-' + type;
            
            var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Remove existing notices
            $('.bae-admin-notice').remove();
            
            // Add new notice
            $('.bae-admin-content').prepend($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Scroll to top to show notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 50
            }, 500);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        BAEInterestsAdmin.init();
    });

    // Handle escape key to close modal
    $(document).keyup(function(e) {
        if (e.keyCode === 27) { // Escape key
            BAEInterestsAdmin.hideModal();
        }
    });

    // Make BAEInterestsAdmin available globally
    window.BAEInterestsAdmin = BAEInterestsAdmin;

})(jQuery);