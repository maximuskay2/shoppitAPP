package com.shoppitplus.shoppit.vendor

import android.R.layout.simple_dropdown_item_1line
import android.app.AlertDialog
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
import com.shoppitplus.shoppit.shared.models.ProductCategory
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import kotlinx.coroutines.launch

class AddProduct : Fragment() {

    private var _binding: FragmentAddProductBinding? = null
    private val binding get() = _binding!!

    private val selectedImages = mutableListOf<Uri>()
    private val MAX_IMAGES = 4

    private var categories = listOf<ProductCategory>()
    private var selectedCategory: ProductCategory? = null
    private val apiClient = ShoppitApiClient()
    private var authToken: String? = null

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentAddProductBinding.inflate(inflater, container, false)
        authToken = AppPrefs.getAuthToken(requireContext())
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
            binding.photoSlot1,
            binding.photoSlot2,
            binding.photoSlot3,
            binding.photoSlot4,
        )

        slots.forEach { slot ->
            slot.setOnClickListener {
                openImagePicker()
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
                val response = apiClient.getProductCategories(authToken!!)
                if (response.success) {
                    categories = response.data
                    updateCategorySpinner()
                    if (categories.isEmpty()) showAddCategoryDialog()
                } else {
                    toast("Failed to load categories")
                }
            } catch (e: Exception) {
                toast("Network error")
            } finally {
                showLoading(false)
            }
        }
    }

    private fun updateCategorySpinner() {
        val names = categories.map { it.name }
        val adapter = ArrayAdapter(requireContext(), simple_dropdown_item_1line, names)
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
                val response = apiClient.createProductCategory(authToken!!, name)
                if (response.success) {
                    toast("Category created")
                    fetchCategories()
                }
            } catch (e: Exception) {
                toast("Failed to create category")
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
        return !binding.etProductName.text.isNullOrBlank() &&
                !binding.etPrice.text.isNullOrBlank() &&
                !binding.etDeliveryTime.text.isNullOrBlank() &&
                selectedCategory != null &&
                selectedImages.isNotEmpty()
    }

    private fun createProduct() {
        showLoading(true)
        binding.btnCreateProduct.isEnabled = false

        lifecycleScope.launch {
            try {
                val name = binding.etProductName.text.toString().trim()
                val price = binding.etPrice.text.toString()
                val deliveryTime = binding.etDeliveryTime.text.toString()
                val discountPrice = binding.etDiscountPrice.text.toString().takeIf { it.isNotBlank() }
                val description = binding.etDescription.text.toString().takeIf { it.isNotBlank() }
                val isActive = binding.switchActive.isChecked.toString()

                val imageBytesList = selectedImages.map { uri ->
                    requireContext().contentResolver.openInputStream(uri)?.use { it.readBytes() }
                        ?: throw IllegalStateException("Cannot read image")
                }

                val response = apiClient.createProduct(
                    token = authToken!!,
                    categoryId = selectedCategory!!.id,
                    name = name,
                    price = price,
                    deliveryTime = deliveryTime,
                    discountPrice = discountPrice,
                    description = description,
                    isActive = isActive,
                    imageDatas = imageBytesList
                )

                if (response.success) {
                    toast("Product created!")
                    findNavController().popBackStack()
                } else {
                    toast(response.message)
                }
            } catch (e: Exception) {
                toast("Error: ${e.message}")
            } finally {
                showLoading(false)
                binding.btnCreateProduct.isEnabled = true
            }
        }
    }

    private fun updateCreateButton() {
        binding.btnCreateProduct.isEnabled = validateAndCreateProduct()
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
