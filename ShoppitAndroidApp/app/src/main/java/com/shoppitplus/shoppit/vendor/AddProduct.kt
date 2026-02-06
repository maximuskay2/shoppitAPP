package com.shoppitplus.shoppit.vendor

import android.R.layout.simple_dropdown_item_1line
import android.app.Activity
import android.app.AlertDialog
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.AdapterView
import android.widget.ArrayAdapter
import android.widget.EditText
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.core.widget.doOnTextChanged
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.databinding.FragmentAddProductBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.utils.ProductCategory
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File


class AddProduct : Fragment() {

    private var _binding: FragmentAddProductBinding? = null
    private val binding get() = _binding!!

    private val selectedImages = mutableListOf<Uri>()
    private val MAX_IMAGES = 4
    private val PICK_IMAGE_REQUEST = 1003

    private var categories = listOf<ProductCategory>()
    private var selectedCategory: ProductCategory? = null

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentAddProductBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupToolbar()
        setupImageGrid()
        setupCategorySpinner()
        setupListeners()
        fetchCategories()
    }

    private fun setupToolbar() {
        binding.btnBack.setOnClickListener {
            findNavController().popBackStack()
        }

    }
    private fun setupCategorySpinner() {
        binding.tvAddCategory.setOnClickListener {
            showAddCategoryDialog()
        }

        updateCategorySpinner()
    }

    private fun setupImageGrid() {
        val slots = listOf(
            binding.photoSlot1 to binding.ivPhoto1,
            binding.photoSlot2 to binding.ivPhoto2,
            binding.photoSlot3 to binding.ivPhoto3,
            binding.photoSlot4 to binding.ivPhoto4,
        )

        slots.forEachIndexed { index, (slot, imageView) ->
            slot.setOnClickListener {
                if (index < selectedImages.size) {
                    // Optional: show full image or remove
                    // For now, allow re-pick
                    openImagePicker()
                } else {
                    openImagePicker()
                }
            }
        }
    }

    private fun openImagePicker() {
        if (selectedImages.size >= MAX_IMAGES) {
            toast("Maximum $MAX_IMAGES photos allowed")
            return
        }
        imagePicker.launch("image/*")
    }

    private val imagePicker =
        registerForActivityResult(ActivityResultContracts.GetMultipleContents()) { uris ->
            val allowed = MAX_IMAGES - selectedImages.size
            selectedImages.addAll(uris.take(allowed))
            updateImageGrid()
            updateCreateButton()
        }


    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode == PICK_IMAGE_REQUEST && resultCode == Activity.RESULT_OK) {
            val uris = mutableListOf<Uri>()
            data?.clipData?.let { clipData ->
                val count = clipData.itemCount.coerceAtMost(MAX_IMAGES - selectedImages.size)
                for (i in 0 until count) {
                    uris.add(clipData.getItemAt(i).uri)
                }
            } ?: data?.data?.let { uris.add(it) }

            selectedImages.addAll(uris)
            updateImageGrid()
            updateCreateButton()
        }
    }

    private fun updateImageGrid() {
        val imageViews = listOf(
            binding.ivPhoto1,
            binding.ivPhoto2,
            binding.ivPhoto3,
            binding.ivPhoto4,
        )
        imageViews.forEachIndexed { index, iv ->
            if (index < selectedImages.size) {
                iv.visibility = View.VISIBLE
                iv.setImageURI(selectedImages[index])
            } else {
                iv.visibility = View.GONE
            }
        }
    }

    private fun fetchCategories() {
        showLoading(true)
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).getProductCategories()
                if (response.success) {
                    categories = response.data
                    updateCategorySpinner()
                    if (categories.isEmpty()) showAddCategoryDialog()
                } else {
                    Toast.makeText(
                        requireContext(),
                        "Failed to load categories",
                        Toast.LENGTH_SHORT
                    ).show()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Network error", Toast.LENGTH_SHORT).show()
            } finally {
                showLoading(false)
            }
        }
    }

    private fun updateCategorySpinner() {
        val names = categories.map { it.name }
        val adapter =
            ArrayAdapter(requireContext(), simple_dropdown_item_1line, names)
        binding.spinnerCategory.adapter = adapter

        binding.spinnerCategory.onItemSelectedListener =
            object : AdapterView.OnItemSelectedListener {
                override fun onItemSelected(
                    parent: AdapterView<*>?,
                    view: View?,
                    position: Int,
                    id: Long
                ) {
                    selectedCategory = categories[position]
                    updateCreateButton()
                }

                override fun onNothingSelected(parent: AdapterView<*>?) {
                    selectedCategory = null
                }
            }
    }

    private fun showAddCategoryDialog() {
        val input = EditText(requireContext()).apply {
            hint = "Category name"
            setPadding(48, 48, 48, 48)
        }
        AlertDialog.Builder(requireContext())
            .setTitle("Add Category")
            .setView(input)
            .setPositiveButton("Create") { _, _ ->
                val name = input.text.toString().trim()
                if (name.isNotBlank()) createCategory(name)
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun createCategory(name: String) {
        showLoading(true)
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).createProductCategory(name)
                if (response.success) {
                    Toast.makeText(requireContext(), "Category created", Toast.LENGTH_SHORT).show()
                    fetchCategories()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Failed to create category", Toast.LENGTH_SHORT)
                    .show()
            } finally {
                showLoading(false)
            }
        }
    }

    private fun setupListeners() {
        listOf(binding.etProductName, binding.etPrice, binding.etDeliveryTime).forEach {
            it.doOnTextChanged { _, _, _, _ -> updateCreateButton() }
        }

        binding.btnCreateProduct.setOnClickListener {
            if (validateAndCreateProduct()) {
                createProduct()
            }
        }
    }

    private fun validateAndCreateProduct(): Boolean {
        var valid = true
        if (binding.etProductName.text.isNullOrBlank()) valid = false
        if (binding.etPrice.text.isNullOrBlank()) valid = false
        if (binding.etDeliveryTime.text.isNullOrBlank()) valid = false
        if (selectedCategory == null) valid = false
        if (selectedImages.isEmpty()) valid = false

        return valid
    }

    private fun createProduct() {
        showLoading(true)
        binding.btnCreateProduct.isEnabled = false

        lifecycleScope.launch {
            try {
                val name = binding.etProductName.text.toString().trim()
                val price = binding.etPrice.text.toString().toIntOrNull() ?: 0
                val deliveryTime = binding.etDeliveryTime.text.toString()
                val discountPrice = binding.etDiscountPrice.text.toString().toIntOrNull()
                val description = binding.etDescription.text.toString().takeIf { it.isNotBlank() }
                val isActive = binding.switchActive.isChecked

                // Prepare text parts
                val categoryIdPart =
                    selectedCategory!!.id.toRequestBody("text/plain".toMediaTypeOrNull())
                val namePart = name.toRequestBody("text/plain".toMediaTypeOrNull())
                val pricePart = price.toString().toRequestBody("text/plain".toMediaTypeOrNull())
                val deliveryPart = deliveryTime.toRequestBody("text/plain".toMediaTypeOrNull())
                val activePart = isActive.toString().toRequestBody("text/plain".toMediaTypeOrNull())

                val discountPart =
                    discountPrice?.toString()?.toRequestBody("text/plain".toMediaTypeOrNull())
                val descPart = description?.toRequestBody("text/plain".toMediaTypeOrNull())

                // Prepare image parts
                val imageParts = selectedImages.mapIndexed { index, uri ->
                    val file = uriToFile(uri)
                    val requestBody = file.asRequestBody("image/*".toMediaTypeOrNull())
                    MultipartBody.Part.createFormData("avatar[$index]", file.name, requestBody)
                }
                val response = RetrofitClient.instance(requireContext()).createProduct(
                    categoryId = categoryIdPart,
                    name = namePart,
                    price = pricePart,
                    deliveryTime = deliveryPart,
                    discountPrice = discountPart,
                    description = descPart,
                    isActive = activePart,
                    avatars = imageParts
                )

                if (response.isSuccessful && response.body()?.success == true) {
                    Toast.makeText(requireContext(), "Product created!", Toast.LENGTH_SHORT).show()
                    findNavController().popBackStack()
                } else {
                    Toast.makeText(
                        requireContext(),
                        response.body()?.message ?: "Failed",
                        Toast.LENGTH_LONG
                    ).show()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                showLoading(false)
                binding.btnCreateProduct.isEnabled = true
            }
        }
    }

    private fun uriToFile(uri: Uri): File {
        val file = File(requireContext().cacheDir, "upload_${System.currentTimeMillis()}.jpg")
        requireContext().contentResolver.openInputStream(uri)
            ?: throw IllegalStateException("Unable to open URI")
        return file.apply {
            requireContext().contentResolver.openInputStream(uri)!!.use { input ->
                outputStream().use { output -> input.copyTo(output) }
            }
        }
    }


    private fun updateCreateButton() {
        val ready = binding.etProductName.text?.isNotBlank() == true &&
                binding.etPrice.text?.isNotBlank() == true &&
                binding.etDeliveryTime.text?.isNotBlank() == true &&
                selectedCategory != null &&
                selectedImages.isNotEmpty()

        binding.btnCreateProduct.isEnabled = ready
    }
    private fun toast(message: String) {
        Toast.makeText(requireContext(), message, Toast.LENGTH_SHORT).show()
    }


    private fun showLoading(show: Boolean) {
        binding.loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
        binding.progressBar.visibility = if (show) View.VISIBLE else View.GONE
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
