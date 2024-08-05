<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocTemplate\StoreRequest;
use App\Http\Resources\DocTemplateFullResource;
use App\Http\Resources\DocTemplateResource;
use App\Http\Response;
use App\Models\DocTemplate;
use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Imagick;
use PhpOffice\PhpWord\Exception\Exception;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class DocTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @param  Organization  $organization
     * @return AnonymousResourceCollection
     *
     * @throws AuthorizationException
     */
    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize(OrganizationPolicy::DOC_TEMPLATES_VIEW, $organization);

        $query = $organization->docTemplates();

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return DocTemplateResource::collection($query->paginate($limit));
        }

        return DocTemplateResource::collection($query->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreRequest  $request
     * @param  Organization  $organization
     * @return DocTemplateFullResource|JsonResponse
     *
     * @throws AuthorizationException
     * @throws Exception
     */
    public function store(StoreRequest $request, Organization $organization): DocTemplateFullResource|JsonResponse
    {
        $this->authorize(OrganizationPolicy::DOC_TEMPLATES_STORE, $organization);

        /**
         * @var FilesystemAdapter $publicStorage
         */
        $publicStorage = Storage::disk(config('filesystems.public'));

        if (! file_exists($publicStorage->path('doc_templates'))) {
            $publicStorage->makeDirectory('doc_templates');
        }

        $fileName = $organization->id.'_'.time().'.docx';
        /** @var UploadedFile $file */
        $file = $request->file('template');

        if ($file->storeAs('doc_templates', $fileName, config('filesystems.private'))) {
            /** @var string $tmpfile */
            $tmpfile = tempnam(sys_get_temp_dir(), 'doc');
            file_put_contents($tmpfile, $file->getContent());

            Settings::setPdfRenderer(Settings::PDF_RENDERER_DOMPDF, base_path('vendor/dompdf/dompdf'));
            $content = IOFactory::load($tmpfile);

            /** @var string $tmpfile2 */
            $tmpfile2 = tempnam(sys_get_temp_dir(), 'pdf');
            $pdfWriter = IOFactory::createWriter($content, 'PDF');
            $pdfWriter->save($tmpfile2);

            /**
             * @var FilesystemAdapter $privateStorage
             */
            $privateStorage = Storage::disk(config('filesystems.private'));
            $privateStorage->putFileAs('doc_templates', $tmpfile2, "$fileName.pdf");

            $imagick = new Imagick();
            $previews = [];
            $page = 0;

            /** @var string $tmpfile3 */
            $tmpfile3 = tempnam(sys_get_temp_dir(), 'jpg');

            while (true) {
                try {
                    $imagick->setResolution(200, 200);
                    $imagick->readImage($tmpfile2."[$page]");
                    $page++;
                    $imagick->scaleImage(1000, 0);
                    $imagick->setImageFormat('jpg');
                    $imagick->setImageCompressionQuality(85);
                    $imagick->setSamplingFactors(['2x2', '1x1', '1x1']);
                    $imagick->stripImage();
                    $imagick->setInterlaceScheme(Imagick::INTERLACE_JPEG);
                    $imagick->setColorspace(Imagick::COLORSPACE_SRGB);
                    $imagick = $imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
                    $imagick->writeImage($tmpfile3);

                    $publicStorage->putFileAs('doc_templates', $tmpfile3, "$fileName.$page.jpg");
                    $previews[] = "$fileName.$page.jpg";
                    $imagick->clear();
                } catch (Throwable $e) {
                    Log::warning($e->getMessage());

                    break;
                }
            }

            $imagick->destroy();

            $template = new TemplateProcessor($tmpfile);
            $docTemplate = DocTemplate::query()->create(
                [
                    'name' => $request->get('name'),
                    'organization_id' => $organization->id,
                    'template' => $fileName,
                    'fields' => array_fill_keys($template->getVariables(), []),
                    'previews' => $previews,
                ]
            );

            unlink($tmpfile);
            unlink($tmpfile2);
            unlink($tmpfile3);

            return new DocTemplateFullResource($docTemplate);
        }

        return Response::error(
            __('validation.mimes', ['attribute' => __('validation.attributes.template'), 'values' => 'docx']),
            ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  Organization  $organization
     * @param  DocTemplate  $docTemplate
     * @return DocTemplateFullResource
     *
     * @throws AuthorizationException
     */
    public function show(Organization $organization, DocTemplate $docTemplate): DocTemplateFullResource
    {
        $this->authorize(OrganizationPolicy::DOC_TEMPLATES_VIEW, $organization);
        $this->checkInOrganization($organization, $docTemplate);

        return new DocTemplateFullResource($docTemplate);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Organization  $organization
     * @param  DocTemplate  $docTemplate
     * @return DocTemplateFullResource|JsonResponse
     *
     * @throws AuthorizationException
     */
    public function update(
        Request $request,
        Organization $organization,
        DocTemplate $docTemplate,
    ): DocTemplateFullResource|JsonResponse {
        $this->authorize(OrganizationPolicy::DOC_TEMPLATES_UPDATE, $organization);
        $this->checkInOrganization($organization, $docTemplate);

        $name = $request->get('name');
        $fields = array_intersect_key($request->get('fields', []), $docTemplate->fields);

        if ($docTemplate->name === null && $name === null) {
            return Response::error(__('validation.required', ['attribute' => 'name']));
        }

        $missingFields = array_diff_key($docTemplate->fields, $fields);

        if (count($missingFields) > 0) {
            $errors = [];

            foreach (array_keys($missingFields) as $name) {
                $errors[] = __('validation.required', ['attribute' => "fields.$name"]);
            }

            return Response::error($errors);
        }

        $docTemplate->fill(
            [
                'name' => $name,
                'fields' => $fields,
            ]
        );
        $docTemplate->save();

        return new DocTemplateFullResource($docTemplate);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Organization  $organization
     * @param  DocTemplate  $docTemplate
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, DocTemplate $docTemplate): JsonResponse
    {
        $this->authorize(OrganizationPolicy::DOC_TEMPLATES_DESTROY, $organization);
        $this->checkInOrganization($organization, $docTemplate);

        $docTemplate->delete();

        return Response::noContent();
    }

    private function checkInOrganization(Organization $organization, DocTemplate $docTemplate): void
    {
        if ($organization->id !== $docTemplate->organization_id) {
            throw new ModelNotFoundException();
        }
    }
}
