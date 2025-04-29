<script type="text/javascript">
    Dropzone.autoDiscover = false;
    var mediaLibraryUrl = '{{ \Javaabu\Mediapicker\Mediapicker::newMediaInstance()->url('index') }}';
    var uploadedFiles = [];

    $(document).ready(function () {
        var mediaLibraryDropzone = new Dropzone('#files-drop', {
            url: mediaLibraryUrl,
            dictResponseError: '{{ __('Error uploading file!') }}',
            dictDefaultMessage: '{{ __('Drop file here to upload') }}',
            dictFileTooBig: '{{ __('File is too big (\{\{filesize\}\} MB). Max filesize: \{\{maxFilesize\}\} MB') }}',
            dictInvalidFileType: '{{ __('You cannot upload files of this type.') }}',
            maxFilesize: {{ floor(\Javaabu\Helpers\Media\AllowedMimeTypes::getMaxFileSize($type ?? '') / 1024) }},
            uploadMultiple: false,
            parallelUploads: 50,
            timeout: 600000,
            acceptedFiles: '{{ AllowedMimeTypes::getAllowedMimeTypesString($type ?? '') }}',
            error: function (file, response) {
                var message = '';

                if (response.hasOwnProperty('errors')) {
                    var errors = response.errors;

                    if (errors.hasOwnProperty('file')) {
                        message = errors.file[0];
                    }
                }

                if (!message) {
                    message = ($.type(response) === "string") ? response
                        : '{{ __('Error uploading file!') }}';
                }

                file.previewElement.classList.add('dz-error');
                _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]');
                _results = [];
                for (_i = 0, _len = _ref.length; _i < _len; _i++) {
                    node = _ref[_i];
                    _results.push(node.textContent = message);
                }
                return _results;
            },
            init: function () {
                this.on('sending', function (file, xhr, form_data) {
                    if ($('#append-data').length > 0) {
                        var append_data = getJsonFormData($('#append-data').find('select, input'));

                        $.each(append_data, function (key, value) {
                            if (Array.isArray(value)) {
                                for (var i = 0; i < value.length; i++) {
                                    form_data.append(key, value[i]);
                                }
                            } else {
                                form_data.append(key, value);
                            }
                        });
                    }
                });

                this.on('success', function (dropzone_file, file) {
                    if (uploadedFiles.length < 1) {
                        $('.files-card').show();
                    }

                    // add to uploaded files
                    uploadedFiles.push(file);

                    var file_name = $('<div />').text(file.file_name).html(); // escape html
                    var file_title = $('<div />').text(file.name).html(); // escape html

                    var file_elem_id = 'file-' + file.id;
                    var delete_link = '';
                    var icon = file.file_type != 'image' ? '<i class="media-icon ' + file.icon + '"></i>' : '';

                    @if($view == 'grid')
                        var html =
                            '<div class="col-lg-2 col-md-3 col-6">' +
                            '<div id="' + file_elem_id + '" title="' + file_title + '" class="card media-thumb square img-header" style="background-image: url(' + file.thumb + ')">' +
                            '<div class="square-content card-body">' +
                            icon +
                            '<a href="' + file.url + '" ' +
                            'data-thumb="' + file.thumb + '" ' +
                            'data-large="' + file.large + '" ' +
                            'data-select-media="' + file.id + '" ' +
                            'data-file-name="' + file.name + '" ' +
                            'data-media-icon="' + file.icon + '" ' +
                             'data-media-type="' + file.file_type + '" ' +
                            'class="view-overlay"></a>' +
                            '</div>' +
                            '</div>' +
                            '</div>';

                        $('.uploaded-files > .row').append(html);
                    @else

                        @if(auth()->user()->canDeleteAnyMedia())
                            var delete_link_id = 'delete-file-' + file.id;
                            delete_link =
                                '<a id="' + delete_link_id + '" class="actions__item zmdi zmdi-delete" href="#" title="Delete">' +
                                ' <span>{{ __('Delete') }}</span>' +
                                '</a>';
                        @endif

                        var html =
                            '<tr id=' + file_elem_id + '>' +
                            '<td class="avatar">' +
                            '<a target="_blank" href="' + file.edit_url + '">' +
                            '<div title="' + file_title + '" class="square img-header" style="background-image: url(' + file.thumb + ')">' +
                            '<div class="square-content">' +
                            icon +
                            '</div>' +
                            '</div>' +
                            '</a>' +
                            '</td>' +
                            '<td data-col="{{ __('Name') }}">' +
                            '<a target="_blank" href="' + file.edit_url + '">' + file.name + '</a>' +
                            '<span class="d-block">' + file_name + '</span>' +
                            '<div class="table-actions actions">' +
                            '<a class="actions__item"><span>ID: ' + file.id + '</span></a> ' +

                            '<a class="actions__item zmdi zmdi-edit" target="_blank" href="' + file.edit_url + '" title="Edit">' +
                            ' <span>{{ __('Edit') }}</span>' +
                            '</a> ' +

                            delete_link +
                            '</div>' +
                            '</td>' +
                            '</tr>';

                        $('.uploaded-files > tbody').append(html);


                        @if(auth()->user()->canDeleteAnyMedia())
                        // Listen to the click event
                        $('.files-card').on('click', '#' + delete_link_id, function (e) {
                            // Make sure the button click doesn't submit the form:
                            e.preventDefault();
                            e.stopPropagation();

                            Swal.fire({
                                title: '{{ __('Are you sure you want to remove this file?') }}',
                                text: '{{ __('You will not be able to undo this delete operation!') }}',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: '{{ __('Yes, Delete!') }}',
                                cancelButtonText: '{{ __('Cancel') }}',
                                scrollbarPadding: false,
                                heightAuto: false,
                                customClass: {
                                    confirmButton: 'btn btn-danger',
                                    cancelButton: 'btn btn-light'
                                }
                            }).then(function(result){
                                if (result.value) {
                                    //remove file extension
                                    var id = file.id;
                                    $.ajax({
                                        type: 'DELETE',
                                        url: mediaLibraryUrl + '/' + id,
                                        success: function (data) {
                                            removeElement(uploadedFiles, file);
                                            $('#' + file_elem_id).remove();

                                            notify(
                                                '{{ __('Success!') }} ',
                                                '{{ __('File') }} ' + file.name + ' {{ __('removed successfully.') }}',
                                                'success'
                                            );

                                            // hide table if 0 files
                                            if (uploadedFiles.length < 1) {
                                                $('.files-card').hide();
                                            }
                                        },
                                        error: function (jqXHR, textStatus, errorThrow) {
                                            if (jqXHR.status != 404) {
                                                notify(
                                                    '{{ __('Error!') }} ',
                                                    '{{ __('Error while removing file') }} ' + file.name,
                                                    'error'
                                                );
                                            }
                                        }
                                    });
                                }
                            });

                        });
                        @endif
                    @endif

                    // remove file from drop zone
                    this.removeFile(dropzone_file);
                });
            }
        });

    });
</script>
