import pytest

@pytest.mark.qemu
def test_hello_world_on_qemu(dut):
    # 'dut' automaticky zastupuje běžící instanci v QEMU
    dut.expect_exact("Hello world!")

