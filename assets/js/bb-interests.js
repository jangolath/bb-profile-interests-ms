/**
 * BAE Profile Interests - Frontend JavaScript
 */
(function($) {
    'use strict';

    var BAEInterests = {
        init: function() {
            this.bindEvents();
            this.initSearch();
        },

        bindEvents: function() {
            // Tab switching for interests categories
            $(document).on('click', '.bae-interests-tabs a', this.handleTabClick);
            
            // Interest management (add/remove)
            $(document).on('change', '.bae-interest-checkbox', this.handleInterestToggle);
            $(document).on('change', '.bae-interest-search-checkbox', this.handleSearchToggle);
            
            // Load more interests
            $(document).on('click', '.bae-load-more-button', this.handleLoadMore);
            
            // Request interest form
            $(document).on('click', '.bae-request-interest-link', this.showRequestForm);
            $(document).on('click', '.bae-cancel-request', this.hideRequestForm);
            $(document).on('submit', '#bae-request-interest-form', this.handleRequestSubmit);
            $(document).on('submit', '#bae-submit-interest-form', this.handleSubmitInterest);
            
            // Interest search
            $(document).on('keyup', '#interests-search', this.handleInterestSearch);
            $(document).on('click', '.bae-interests-search-results a', this.handleInterestSelect);
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.bp-search').length) {
                    $('.bae-interests-search-results').hide();
                }
            });
        },

        handleTabClick: function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var target = $this.attr('href');
            
            // Activate tab
            $('.bae-interests-tabs a').removeClass('active');
            $this.addClass('active');
            
            // Show tab content
            $('.bae-interests-tab-pane').removeClass('active');
            $(target).addClass('active');
        },

        handleInterestToggle: function() {
            var $this = $(this);
            var interestId = $this.data('interest-id');
            var $item = $this.closest('.bae-interest-manage-item');
            var $searchOption = $item.find('.bae-interest-search-option');
            
            if ($this.is(':checked')) {
                // Add interest
                $item.addClass('selected');
                $searchOption.show();
                
                BAEInterests.addInterestToProfile(interestId, $item.find('.bae-interest-search-checkbox').is(':checked'));
            } else {
                // Remove interest
                $item.removeClass('selected');
                $searchOption.hide();
                
                BAEInterests.removeInterestFromProfile(interestId);
            }
        },

        handleSearchToggle: function() {
            var $this = $(this);
            var interestId = $this.data('interest-id');
            
            BAEInterests.addInterestToProfile(interestId, $this.is(':checked'));
        },

        addInterestToProfile: function(interestId, showInSearch) {
            $.post(baeInterests.ajaxUrl, {
                action: 'bae_add_interest_to_profile',
                nonce: baeInterests.nonce,
                interest_id: interestId,
                show_in_search: showInSearch ? 1 : 0
            }, function(response) {
                if (!response.success) {
                    BAEInterests.showMessage(response.data.message, 'error');
                }
            }).fail(function() {
                BAEInterests.showMessage('An error occurred. Please try again.', 'error');
            });
        },

        removeInterestFromProfile: function(interestId) {
            $.post(baeInterests.ajaxUrl, {
                action: 'bae_remove_interest_from_profile',
                nonce: baeInterests.nonce,
                interest_id: interestId
            }, function(response) {
                if (!response.success) {
                    BAEInterests.showMessage(response.data.message, 'error');
                }
            }).fail(function() {
                BAEInterests.showMessage('An error occurred. Please try again.', 'error');
            });
        },

        handleLoadMore: function() {
            var $this = $(this);
            var categoryId = $this.data('category-id');
            var offset = $this.data('offset');
            var $container = $this.closest('.bae-interests-tab-pane').find('.bae-interests-grid');
            var $spinner = $this.siblings('.bae-spinner');
            
            // Disable button and show spinner
            $this.prop('disabled', true);
            $spinner.show();
            
            $.post(baeInterests.ajaxUrl, {
                action: 'bae_load_more_interests',
                nonce: baeInterests.nonce,
                category_id: categoryId,
                offset: offset,
                user_id: typeof baeInterests.userId !== 'undefined' ? baeInterests.userId : 0
            }, function(response) {
                if (response.success) {
                    // Append new interests
                    $container.append(response.data.html);
                    
                    // Update offset
                    $this.data('offset', response.data.offset);
                    
                    // Hide button if no more interests
                    if (!response.data.more) {
                        $this.parent().hide();
                    }
                } else {
                    BAEInterests.showMessage(response.data.message, 'error');
                }
            }).fail(function() {
                BAEInterests.showMessage('An error occurred. Please try again.', 'error');
            }).always(function() {
                // Re-enable button and hide spinner
                $this.prop('disabled', false);
                $spinner.hide();
            });
        },

        showRequestForm: function(e) {
            e.preventDefault();
            $('.bae-request-interest-form').slideDown();
        },

        hideRequestForm: function() {
            $('.bae-request-interest-form').slideUp();
            $('#bae-request-interest-form').trigger('reset');
            $('.bae-form-message').hide();
        },

        handleRequestSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var $spinner = $form.find('.bae-spinner');
            var $message = $form.find('.bae-form-message');
            
            // Disable submit button and show spinner
            $submit.prop('disabled', true);
            $spinner.show();
            
            // Prepare form data
            var formData = {
                action: 'bae_submit_interest_request',
                nonce: baeInterests.nonce,
                interest_name: $form.find('#request-interest-name').val(),
                category_id: $form.find('#request-interest-category').val(),
                notes: $form.find('#request-interest-notes').val()
            };
            
            // Send AJAX request
            $.post(baeInterests.ajaxUrl, formData, function(response) {
                if (response.success) {
                    // Show success message
                    $message.removeClass('error').addClass('success').html('<p>' + response.data.message + '</p>').show();
                    
                    // Reset form
                    $form.trigger('reset');
                    
                    // Hide form after 3 seconds
                    setTimeout(function() {
                        BAEInterests.hideRequestForm();
                    }, 3000);
                } else {
                    // Show error message
                    $message.removeClass('success').addClass('error').html('<p>' + response.data.message + '</p>').show();
                }
            }).fail(function() {
                // Show generic error message
                $message.removeClass('success').addClass('error').html('<p>An error occurred. Please try again.</p>').show();
            }).always(function() {
                // Re-enable submit button and hide spinner
                $submit.prop('disabled', false);
                $spinner.hide();
            });
        },

        handleSubmitInterest: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var $spinner = $form.find('.bae-spinner');
            var $message = $form.find('.bae-form-message');
            
            // Disable submit button and show spinner
            $submit.prop('disabled', true);
            $spinner.show();
            
            // Prepare form data
            var formData = {
                action: 'bae_submit_interest_request',
                nonce: baeInterests.nonce,
                interest_name: $form.find('#interest-name').val(),
                category_id: $form.find('#interest-category').val(),
                notes: $form.find('#interest-notes').val()
            };
            
            // Send AJAX request
            $.post(baeInterests.ajaxUrl, formData, function(response) {
                if (response.success) {
                    // Show success message (from shortcode attribute or default)
                    var successMessage = typeof baeInterests.successMessage !== 'undefined' ? 
                        baeInterests.successMessage : response.data.message;
                    $message.removeClass('error').addClass('success').html('<p>' + successMessage + '</p>').show();
                    
                    // Reset form
                    $form.trigger('reset');
                } else {
                    // Show error message
                    $message.removeClass('success').addClass('error').html('<p>' + response.data.message + '</p>').show();
                }
            }).fail(function() {
                // Show generic error message
                $message.removeClass('success').addClass('error').html('<p>An error occurred. Please try again.</p>').show();
            }).always(function() {
                // Re-enable submit button and hide spinner
                $submit.prop('disabled', false);
                $spinner.hide();
            });
        },

        initSearch: function() {
            // Initialize interest search functionality
            var searchTimeout;
            
            // Handle search input with debouncing
            $(document).on('keyup', '#interests-search', function() {
                clearTimeout(searchTimeout);
                var $this = $(this);
                var term = $this.val().trim();
                
                if (term.length < 2) {
                    $('.bae-interests-search-results').hide();
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    BAEInterests.searchInterests(term);
                }, 300);
            });
        },

        handleInterestSearch: function() {
            var $this = $(this);
            var term = $this.val().trim();
            
            if (term.length < 2) {
                $('.bae-interests-search-results').hide();
                return;
            }
            
            BAEInterests.searchInterests(term);
        },

        searchInterests: function(term) {
            $.get(baeInterests.ajaxUrl, {
                action: 'bae_search_interests',
                term: term
            }, function(response) {
                if (response.success && response.data.length > 0) {
                    var html = '';
                    $.each(response.data, function(index, interest) {
                        html += '<li><a href="' + interest.url + '" data-interest-id="' + interest.id + '">';
                        html += '<strong>' + interest.name + '</strong>';
                        html += '<span class="category">in ' + interest.category + '</span>';
                        html += '</a></li>';
                    });
                    
                    $('.bae-interests-search-results ul').html(html);
                    $('.bae-interests-search-results').show();
                } else {
                    $('.bae-interests-search-results').hide();
                }
            });
        },

        handleInterestSelect: function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            window.location.href = url;
        },

        showMessage: function(message, type) {
            var messageClass = type === 'error' ? 'notice-error' : 'notice-success';
            var $notice = $('<div class="notice ' + messageClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Find a good place to show the message
            var $target = $('.bae-interests-manage, .bae-interests-profile, .wrap').first();
            if ($target.length) {
                $target.prepend($notice);
                
                // Auto-dismiss after 5 seconds
                setTimeout(function() {
                    $notice.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        BAEInterests.init();
    });

    // Make BAEInterests available globally if needed
    window.BAEInterests = BAEInterests;

})(jQuery);